<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SaleItem extends Model {
    use \App\Models\Concerns\BelongsToCompany;
    protected $guarded=[];
    public function sale(){ return $this->belongsTo(Sale::class); }
    public function product(){ return $this->belongsTo(Product::class); }
}
