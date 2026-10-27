<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class FinancialYear extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['start_date'=>'date','end_date'=>'date','closed_at'=>'datetime','closing_summary'=>'array'];
    }

    public function partyBalances(){return $this->hasMany(FinancialYearPartyBalance::class);}
    public function stockBalances(){return $this->hasMany(FinancialYearStockBalance::class);}
    public function accountBalances(){return $this->hasMany(FinancialYearAccountBalance::class);}
    public function closedBy(){return $this->belongsTo(User::class,'closed_by');}
}
