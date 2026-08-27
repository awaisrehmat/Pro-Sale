<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StockAdjustment extends Model { use \App\Models\Concerns\BelongsToCompany; protected $guarded=[]; protected function casts(): array { return ['adjustment_date'=>'date']; } }
