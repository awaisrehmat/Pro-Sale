<?php
namespace App\Services;
use App\Models\{Payment,Product,Purchase};
use App\Support\Numbers;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Arr;
class PurchaseService {
    public function __construct(private StockService $stock){}
    public function create(array $data,int $userId): Purchase {
        return DB::transaction(function()use($data,$userId){
            $subtotal=collect($data['items'])->sum(fn($i)=>round($i['quantity']*$i['unit_price']-($i['discount']??0),2));
            $total=round($subtotal-($data['discount']??0)+($data['additional_cost']??0),2); $paid=(float)($data['paid_amount']??0);
            if($paid>$total) throw ValidationException::withMessages(['paid_amount'=>'Paid amount cannot exceed grand total.']);
            $purchase=Purchase::create([...Arr::except($data,'items'),'purchase_number'=>Numbers::next(Purchase::class,'purchase_number','PUR'),'subtotal'=>$subtotal,
                'grand_total'=>$total,'paid_amount'=>$paid,'due_amount'=>$total-$paid,'payment_status'=>$paid<=0?'unpaid':($paid<$total?'partial':'paid'),'status'=>'completed','created_by'=>$userId]);
            foreach($data['items'] as $item){
                $p=Product::query()->lockForUpdate()->where('is_active',true)->findOrFail($item['product_id']);
                $qty=(float)$item['quantity']; $price=(float)$item['unit_price']; $oldStock=(float)$p->current_stock; $oldCost=(float)$p->average_cost;
                $line=round($qty*$price-($item['discount']??0),2);
                $purchase->items()->create(['product_id'=>$p->id,'quantity'=>$qty,'unit_price'=>$price,'discount'=>$item['discount']??0,'line_total'=>$line,'previous_average_cost'=>$oldCost]);
                $newCost=($oldStock+$qty)>0?round((($oldStock*$oldCost)+($qty*$price))/($oldStock+$qty),2):0;
                $p->update(['average_cost'=>$newCost,'purchase_price'=>$price]);
                $this->stock->move($p,$qty,0,'purchase',$purchase,$purchase->purchase_number,$price,$userId);
            }
            if($paid>0) Payment::create(['payment_number'=>Numbers::next(Payment::class,'payment_number','PAY'),'payment_date'=>$data['purchase_date'],
                'payment_type'=>'supplier_payment','supplier_id'=>$purchase->supplier_id,'purchase_id'=>$purchase->id,'amount'=>$paid,
                'payment_method'=>$data['payment_method'],'created_by'=>$userId]);
            return $purchase->load('supplier','items.product','payments');
        });
    }
    public function cancel(Purchase $purchase,int $userId): Purchase {
        return DB::transaction(function()use($purchase,$userId){
            $purchase=Purchase::query()->lockForUpdate()->findOrFail($purchase->id);
            if($purchase->status==='cancelled') throw ValidationException::withMessages(['status'=>'Purchase is already cancelled.']);
            foreach($purchase->items as $item){
                $p=Product::query()->lockForUpdate()->findOrFail($item->product_id);
                $this->stock->move($p,0,(float)$item->quantity,'purchase_cancellation',$purchase,$purchase->purchase_number,(float)$item->unit_price,$userId);
                $p->update(['average_cost'=>(float)$item->previous_average_cost]);
            }
            $purchase->payments()->update(['is_reversed'=>true]); $purchase->update(['status'=>'cancelled']);
            return $purchase->fresh(['supplier','items.product','payments']);
        });
    }
}
