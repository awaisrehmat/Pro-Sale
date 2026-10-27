<?php

namespace App\Tenancy;

use App\Models\FinancialYear;
use RuntimeException;

class FinancialYearContext
{
    private ?FinancialYear $financialYear = null;
    public function set(FinancialYear $financialYear):void{$this->financialYear=$financialYear;}
    public function clear():void{$this->financialYear=null;}
    public function financialYear():?FinancialYear{return $this->financialYear;}
    public function id():?int{return $this->financialYear?->id;}
    public function requireId():int{return $this->id()??throw new RuntimeException('No active financial year has been selected.');}
}
