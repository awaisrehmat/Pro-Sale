<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Tenancy\CompanyContext;
class AuthController extends Controller {
    public function login(Request $r){
        $data=$r->validate(['email'=>'required|email','password'=>'required|string']);
        if(!Auth::attempt($data)) throw ValidationException::withMessages(['email'=>'The credentials are incorrect.']);
        $user=$r->user();
        if(! $user->is_active){Auth::logout();throw ValidationException::withMessages(['email'=>'This user account is inactive.']);}
        $companies=$user->companies()->where('companies.is_active',true)->orderBy('companies.name')->get(['companies.id','companies.name','companies.code']);
        if($companies->isEmpty()){Auth::logout();throw ValidationException::withMessages(['email'=>'This user is not assigned to an active company.']);}
        app(CompanyContext::class)->set($companies->first());
        $permissions=$user->getAllPermissions()->pluck('name')->when(!$user->is_group_admin,fn($items)=>$items->reject(fn($name)=>in_array($name,['companies.manage','reports.consolidated'])))->values();
        $user->load('roles'); return response()->json(['success'=>true,'message'=>'Login successful.','data'=>['user'=>$user,'permissions'=>$permissions,'companies'=>$companies,'current_company'=>$companies->first(),'token'=>$user->createToken('inventory-app')->plainTextToken]]);
    }
    public function logout(Request $r){$r->user()->currentAccessToken()?->delete();return response()->json(['success'=>true,'message'=>'Logged out successfully.','data'=>null]);}
}
