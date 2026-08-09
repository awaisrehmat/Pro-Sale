<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Product extends Model {
    use SoftDeletes;
    protected $guarded = [];
    protected $appends = ['category_name', 'subcategory_name'];
    protected function casts(): array { return ['is_active'=>'boolean','purchase_price'=>'decimal:2','sale_price'=>'decimal:2','average_cost'=>'decimal:2','minimum_stock_level'=>'decimal:3','current_stock'=>'decimal:3']; }
    public function stockMovements() { return $this->hasMany(StockMovement::class); }
    public function category() { return $this->belongsTo(ProductCategory::class, 'product_category_id'); }
    public function subcategory() { return $this->belongsTo(ProductSubcategory::class, 'product_subcategory_id'); }
    public function getCategoryNameAttribute() { return $this->category?->name; }
    public function getSubcategoryNameAttribute() { return $this->subcategory?->name; }
}
