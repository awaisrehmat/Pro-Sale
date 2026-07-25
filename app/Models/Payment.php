<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model {
    protected $guarded=[];
    protected function casts(): array { return ['payment_date'=>'date','is_reversed'=>'boolean']; }
    public function supplier(){ return $this->belongsTo(Supplier::class); }
    public function customer(){ return $this->belongsTo(Customer::class); }
}
