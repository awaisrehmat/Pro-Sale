<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserAdministrationController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles:id,name')->with('companies:id,name,code')->where('group_id',$request->user()->group_id)
            ->when($request->search, fn ($query, $search) => $query->where(
                fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->ok($users);
    }

    public function roles()
    {
        return $this->ok(Role::with('permissions:id,name')->where('guard_name', 'sanctum')->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'role' => ['required', 'exists:roles,name'],
            'is_active' => ['required', 'boolean'],
            'company_ids'=>['required','array','min:1'],'company_ids.*'=>['integer',Rule::exists('companies','id')->where('group_id',$request->user()->group_id)],
        ]);

        $user = User::create([
            'group_id'=>$request->user()->group_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => $data['is_active'],
        ]);
        $user->assignRole(Role::where('name', $data['role'])->where('guard_name', 'sanctum')->firstOrFail());
        $user->companies()->sync($data['company_ids']);

        return $this->ok($user->load('roles:id,name'), 'User created successfully.', 201);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->group_id===$request->user()->group_id,403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'role' => ['required', 'exists:roles,name'],
            'is_active' => ['required', 'boolean'],
            'company_ids'=>['required','array','min:1'],'company_ids.*'=>['integer',Rule::exists('companies','id')->where('group_id',$request->user()->group_id)],
        ]);

        if ($request->user()->is($user) && ! $data['is_active']) {
            throw ValidationException::withMessages(['is_active' => 'You cannot deactivate your own account.']);
        }

        if ($user->hasRole('Administrator') && $data['role'] !== 'Administrator' && User::role('Administrator')->count() <= 1) {
            throw ValidationException::withMessages(['role' => 'At least one Administrator account is required.']);
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $data['is_active'],
            ...(! empty($data['password']) ? ['password' => $data['password']] : []),
        ]);
        $user->syncRoles([Role::where('name', $data['role'])->where('guard_name', 'sanctum')->firstOrFail()]);
        $user->companies()->sync($data['company_ids']);

        if (! $user->is_active) $user->tokens()->delete();

        return $this->ok($user->fresh()->load('roles:id,name'), 'User updated successfully.');
    }

    private function ok($data, string $message = 'Users retrieved successfully.', int $status = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }
}
