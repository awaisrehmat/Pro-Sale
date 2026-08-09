<?php
namespace App\Services;
use App\Models\{Payment,Product,Sale};
use App\Support\Numbers;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Arr;
class SaleService {
    public function __construct(private StockService $stock){}
    public function create(array $data,int $userId): Sale {
        return DB::transaction(function()use($data,$userId){
            $subtotal=collect($data['items'])->sum(fn($i)=>round($i['quantity']*$i['unit_price']-($i['discount']??0),2));
            $total=round($subtotal-($data['discount']??0)+($data['tax']??0),2); $paid=(float)($data['paid_amount']??0);
            if($paid>$total) throw ValidationException::withMessages(['paid_amount'=>'Paid amount cannot exceed grand total.']);
            $sale=Sale::create([...Arr::except($data,'items'),'sale_number'=>Numbers::next('SI',$data['sale_date']),'subtotal'=>$subtotal,'grand_total'=>$total,
                'paid_amount'=>$paid,'due_amount'=>$total-$paid,'payment_status'=>$paid<=0?'unpaid':($paid<$total?'partial':'paid'),'status'=>'completed','created_by'=>$userId]);
            foreach($data['items'] as $item){
                $p=Product::query()->lockForUpdate()->where('is_active',true)->findOrFail($item['product_id']); $qty=(float)$item['quantity'];
                if($qty>(float)$p->current_stock) throw ValidationException::withMessages(['items'=>"Only {$p->current_stock} units of {$p->name} are available."]);
                $line=round($qty*$item['unit_price']-($item['discount']??0),2);
                $sale->items()->create(['product_id'=>$p->id,'quantity'=>$qty,'unit_price'=>$item['unit_price'],'discount'=>$item['discount']??0,'line_total'=>$line,'unit_cost'=>$p->average_cost]);
                $this->stock->move($p,0,$qty,'sale',$sale,$sale->sale_number,(float)$p->average_cost,$userId,null,$data['sale_date']);
            }
            if($paid>0) Payment::create(['payment_number'=>Numbers::next('RV',$data['sale_date']),'payment_date'=>$data['sale_date'],
                'payment_type'=>'customer_payment','customer_id'=>$sale->customer_id,'sale_id'=>$sale->id,'amount'=>$paid,'payment_method'=>$data['payment_method'],'created_by'=>$userId]);
            return $sale->load('customer','items.product','payments');
        });
    }
    public function cancel(Sale $sale,int $userId): Sale {
        return DB::transaction(function()use($sale,$userId){
            $sale=Sale::query()->lockForUpdate()->findOrFail($sale->id);
            if($sale->status==='cancelled') throw ValidationException::withMessages(['status'=>'Sale is already cancelled.']);
            foreach($sale->items as $item){$p=Product::query()->lockForUpdate()->findOrFail($item->product_id);
                $this->stock->move($p,(float)$item->quantity,0,'sale_cancellation',$sale,$sale->sale_number,(float)$item->unit_cost,$userId);}
            $sale->payments()->update(['is_reversed'=>true]); $sale->update(['status'=>'cancelled']);
            return $sale->fresh(['customer','items.product','payments']);
        });
    }
}
