<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductSubcategory extends Model {
    protected $guarded=[];
    protected function casts():array{return ['is_active'=>'boolean'];}
    public function category(){return $this->belongsTo(ProductCategory::class,'product_category_id');}
    public function products(){return $this->hasMany(Product::class);}
}
