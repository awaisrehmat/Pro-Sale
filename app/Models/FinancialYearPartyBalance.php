<?php
namespace App\Models;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
class FinancialYearPartyBalance extends Model {use BelongsToCompany;protected $guarded=[];public function financialYear(){return $this->belongsTo(FinancialYear::class);}}
