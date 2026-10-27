<?php

namespace App\Support;

use App\Tenancy\FinancialYearContext;
use Carbon\Carbon;
use Closure;

final class FinancialYearRule
{
    public static function date():Closure
    {
        return function(string $attribute,mixed $value,Closure $fail):void{
            $year=app(FinancialYearContext::class)->financialYear();
            if(!$year||$year->status!=='active'){$fail('No open financial year is available.');return;}
            try{$date=Carbon::parse($value);}catch(\Throwable){return;}
            if($date->lt($year->start_date)||$date->gt($year->end_date))$fail("The {$attribute} must be within financial year {$year->name} ({$year->start_date->format('d M Y')} to {$year->end_date->format('d M Y')}).");
        };
    }
}
