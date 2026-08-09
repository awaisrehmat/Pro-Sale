<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductCategory extends Model {
    protected $guarded=[];
    protected function casts():array{return ['is_active'=>'boolean'];}
    public function subcategories(){return $this->hasMany(ProductSubcategory::class);}
    public function products(){return $this->hasMany(Product::class);}
}
