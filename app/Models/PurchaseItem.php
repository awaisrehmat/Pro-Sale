<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseItem extends Model {
    use \App\Models\Concerns\BelongsToCompany;
    protected $guarded=[];
    public function purchase(){ return $this->belongsTo(Purchase::class); }
    public function product(){ return $this->belongsTo(Product::class); }
}
