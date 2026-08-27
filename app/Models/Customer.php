<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Customer extends Model {
    use SoftDeletes, \App\Models\Concerns\BelongsToCompany;
    protected $guarded=[];
    protected function casts(): array { return ['is_active'=>'boolean','is_walk_in'=>'boolean','opening_balance'=>'decimal:2']; }
    public function sales(){ return $this->hasMany(Sale::class); }
    public function payments(){ return $this->hasMany(Payment::class); }
}
