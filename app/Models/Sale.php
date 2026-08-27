<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Sale extends Model {
    use \App\Models\Concerns\BelongsToCompany;
    protected $guarded=[];
    protected function casts(): array { return ['sale_date'=>'date']; }
    public function customer(){ return $this->belongsTo(Customer::class); }
    public function items(){ return $this->hasMany(SaleItem::class); }
    public function payments(){ return $this->hasMany(Payment::class); }
}
