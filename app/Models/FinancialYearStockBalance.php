<?php
namespace App\Models;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
class FinancialYearStockBalance extends Model {use BelongsToCompany;protected $guarded=[];public function financialYear(){return $this->belongsTo(FinancialYear::class);}public function product(){return $this->belongsTo(Product::class);}}
