<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model {
    use \App\Models\Concerns\BelongsToCompany;
    protected $guarded=[];
    protected function casts(): array { return ['payment_date'=>'date','is_reversed'=>'boolean']; }
    public function supplier(){ return $this->belongsTo(Supplier::class); }
    public function customer(){ return $this->belongsTo(Customer::class); }
    public function purchase(){ return $this->belongsTo(Purchase::class); }
    public function sale(){ return $this->belongsTo(Sale::class); }
}
