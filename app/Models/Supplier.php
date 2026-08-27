<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Supplier extends Model {
    use SoftDeletes, \App\Models\Concerns\BelongsToCompany;
    protected $guarded=[];
    protected function casts(): array { return ['is_active'=>'boolean','opening_balance'=>'decimal:2']; }
    public function purchases(){ return $this->hasMany(Purchase::class); }
    public function payments(){ return $this->hasMany(Payment::class); }
}
