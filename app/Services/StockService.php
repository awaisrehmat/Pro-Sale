<?php
namespace App\Services;
use App\Models\{Product,StockAdjustment,StockMovement};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class StockService {
    public function move(Product $product, float $in, float $out, string $type, object $reference, string $number, float $cost, int $userId, ?string $notes=null, ?string $movementDate=null): void {
        $before=(float)$product->current_stock; $after=round($before+$in-$out,3);
        if($after<0) throw ValidationException::withMessages(['quantity'=>"Only {$before} units of {$product->name} are available."]);
        $product->update(['current_stock'=>$after]);
        StockMovement::create(['product_id'=>$product->id,'movement_date'=>$movementDate ?: now()->toDateString(),'movement_type'=>$type,
            'reference_type'=>$reference::class,'reference_id'=>$reference->id,'reference_number'=>$number,'quantity_in'=>$in,
            'quantity_out'=>$out,'stock_before'=>$before,'stock_after'=>$after,'unit_cost'=>$cost,'notes'=>$notes,'created_by'=>$userId]);
    }
    public function adjust(array $data,int $userId): StockAdjustment {
        return DB::transaction(function()use($data,$userId){
            $p=Product::query()->lockForUpdate()->findOrFail($data['product_id']);
            $a=StockAdjustment::create(['product_id'=>$p->id,'adjustment_date'=>$data['adjustment_date'],'adjustment_type'=>$data['adjustment_type'],
                'quantity'=>$data['quantity'],'reason'=>$data['reason'],'created_by'=>$userId]);
            $increase=$data['adjustment_type']==='increase';
            $this->move($p,$increase?(float)$data['quantity']:0,$increase?0:(float)$data['quantity'],
                $increase?'positive_adjustment':'negative_adjustment',$a,'ADJ-'.str_pad($a->id,6,'0',STR_PAD_LEFT),(float)$p->average_cost,$userId,$data['reason'],$data['adjustment_date']);
            return $a;
        });
    }
}
