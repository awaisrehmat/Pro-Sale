<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StockMovement extends Model {
    use \App\Models\Concerns\BelongsToCompany;
    protected $guarded=[];
    protected function casts(): array { return ['movement_date'=>'date']; }
    public function product(){ return $this->belongsTo(Product::class); }
}
