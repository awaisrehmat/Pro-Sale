<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Expense,ExpenseCategory};
use App\Services\ExpenseService;
use App\Support\TenantRule;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('category', 'payment')
            ->when($request->search, function ($builder, $search) {
                $builder->where(function ($filter) use ($search) {
                    $filter->where('expense_number', 'like', "%$search%")
                        ->orWhere('payee_name', 'like', "%$search%")
                        ->orWhere('reference_number', 'like', "%$search%")
                        ->orWhereHas('category', fn ($category) => $category->where('name', 'like', "%$search%"));
                });
            })
            ->when($request->expense_category_id, fn ($builder, $id) => $builder->where('expense_category_id', $id))
            ->when($request->payment_method, fn ($builder, $method) => $builder->where('payment_method', $method))
            ->when($request->status, fn ($builder, $status) => $builder->where('status', $status))
            ->when($request->date_from, fn ($builder, $date) => $builder->whereDate('expense_date', '>=', $date))
            ->when($request->date_to, fn ($builder, $date) => $builder->whereDate('expense_date', '<=', $date));

        $summaryQuery = clone $query;
        $summary = [
            'filtered_total' => (float) (clone $summaryQuery)->where('status', 'posted')->sum('amount'),
            'posted_count' => (clone $summaryQuery)->where('status', 'posted')->count(),
            'cancelled_count' => (clone $summaryQuery)->where('status', 'cancelled')->count(),
            'today_total' => (float) Expense::where('status', 'posted')->whereDate('expense_date', today())->sum('amount'),
            'month_total' => (float) Expense::where('status', 'posted')->whereBetween('expense_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('amount'),
        ];

        return $this->ok(['records' => $query->latest('expense_date')->latest('id')->paginate(20), 'summary' => $summary]);
    }

    public function show(Expense $expense)
    {
        return $this->ok($expense->load('category', 'payment', 'creator:id,name'));
    }

    public function store(Request $request, ExpenseService $service)
    {
        $data = $request->validate([
            'expense_date' => 'required|date',
            'expense_category_id' => ['required', TenantRule::exists('expense_categories')->where('is_active', true)],
            'payee_name' => 'required|string|max:150',
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'required|in:cash,bank_transfer,card,other',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        return $this->ok($service->create($data, $request->user()->id), 'Expense and payment voucher created successfully.', 201);
    }

    public function cancel(Request $request, Expense $expense, ExpenseService $service)
    {
        return $this->ok($service->cancel($expense, $request->user()->id), 'Expense cancelled and payment voucher reversed.');
    }

    public function categories()
    {
        return $this->ok(ExpenseCategory::withCount('expenses')->orderBy('name')->get());
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', TenantRule::unique('expense_categories', 'name')],
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);
        return $this->ok(ExpenseCategory::create($data), 'Expense category created.', 201);
    }

    public function updateCategory(Request $request, ExpenseCategory $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', TenantRule::unique('expense_categories', 'name')->ignore($category)],
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);
        $category->update($data);
        return $this->ok($category->fresh()->loadCount('expenses'), 'Expense category updated.');
    }

    public function destroyCategory(ExpenseCategory $category)
    {
        if ($category->expenses()->exists()) {
            throw ValidationException::withMessages(['category' => 'This category is used by expenses and cannot be deleted. Deactivate it instead.']);
        }
        $category->delete();
        return $this->ok(null, 'Expense category deleted.');
    }

    private function ok($data, string $message = 'Expenses retrieved.', int $status = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }
}
