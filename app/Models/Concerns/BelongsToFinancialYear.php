<?php

namespace App\Models\Concerns;

use App\Models\FinancialYear;
use App\Tenancy\FinancialYearContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToFinancialYear
{
    protected static function bootBelongsToFinancialYear():void
    {
        static::creating(function($model){$model->financial_year_id??=app(FinancialYearContext::class)->requireId();});
    }
    public function financialYear(){return $this->belongsTo(FinancialYear::class);}
    public function scopeCurrentFinancialYear(Builder $query):Builder{return $query->where($query->getModel()->qualifyColumn('financial_year_id'),app(FinancialYearContext::class)->requireId());}
}
