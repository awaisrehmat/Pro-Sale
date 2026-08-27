<?php

namespace App\Services;

use App\Models\{Expense,Payment};
use App\Support\Numbers;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function create(array $data, int $userId): Expense
    {
        return DB::transaction(function () use ($data, $userId) {
            $expense = Expense::create([
                ...$data,
                'expense_number' => Numbers::next('EX', $data['expense_date']),
                'status' => 'posted',
                'created_by' => $userId,
            ]);

            Payment::create([
                'payment_number' => Numbers::next('PV', $data['expense_date']),
                'payment_date' => $data['expense_date'],
                'payment_type' => 'expense_payment',
                'expense_id' => $expense->id,
                'payee_name' => $data['payee_name'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            return $expense->load('category', 'payment');
        });
    }

    public function cancel(Expense $expense, int $userId): Expense
    {
        return DB::transaction(function () use ($expense, $userId) {
            $expense = Expense::query()->lockForUpdate()->findOrFail($expense->id);
            if ($expense->status === 'cancelled') {
                throw ValidationException::withMessages(['status' => 'Expense is already cancelled.']);
            }
            $expense->payment()->update(['is_reversed' => true]);
            $expense->update(['status' => 'cancelled', 'cancelled_by' => $userId, 'cancelled_at' => now()]);

            return $expense->fresh(['category', 'payment']);
        });
    }
}
