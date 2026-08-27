<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['expense_date' => 'date', 'cancelled_at' => 'datetime'];
    }

    public function category() { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function payment() { return $this->hasOne(Payment::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function cancelledBy() { return $this->belongsTo(User::class, 'cancelled_by'); }
}
