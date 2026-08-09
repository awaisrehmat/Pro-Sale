<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class UnitOfMeasurement extends Model {
    protected $table='units_of_measurement';
    protected $guarded=[];
    protected function casts():array{return ['is_active'=>'boolean','decimal_places'=>'integer'];}
    public function products(){return $this->hasMany(Product::class);}
}
