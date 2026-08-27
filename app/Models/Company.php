<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Company extends Model { protected $guarded=[]; protected function casts():array{return ['is_active'=>'boolean'];} public function group(){return $this->belongsTo(Group::class);} public function users(){return $this->belongsToMany(User::class)->withTimestamps();} }
