<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Group extends Model { protected $guarded=[]; protected function casts():array{return ['is_active'=>'boolean'];} public function companies(){return $this->hasMany(Company::class);} public function users(){return $this->hasMany(User::class);} }
