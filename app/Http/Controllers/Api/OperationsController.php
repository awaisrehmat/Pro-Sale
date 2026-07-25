<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{Customer,Payment,Product,Purchase,Sale,StockMovement,Supplier};
use App\Services\StockService;
use App\Support\Numbers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class OperationsController extends Controller {
    public function dashboard(){
        $month=now()->startOfMonth();$sales=Sale::where('status','completed')->whereDate('sale_date','>=',$month)->get();
        $cogs=\App\Models\SaleItem::whereIn('sale_id',$sales->pluck('id'))->selectRaw('SUM(quantity * unit_cost) total')->value('total')??0;
        return $this->ok(['total_products'=>Product::count(),'total_stock'=>(float)Product::sum('current_stock'),'stock_value'=>(float)Product::selectRaw('SUM(current_stock*average_cost) value')->value('value'),
            'purchases_today'=>(float)Purchase::where('status','completed')->whereDate('purchase_date',today())->sum('grand_total'),'sales_today'=>(float)Sale::where('status','completed')->whereDate('sale_date',today())->sum('grand_total'),
            'purchase_month'=>(float)Purchase::where('status','completed')->whereDate('purchase_date','>=',$month)->sum('grand_total'),'sales_month'=>(float)$sales->sum('grand_total'),
            'gross_profit_month'=>(float)$sales->sum('subtotal')-(float)$cogs,'low_stock'=>Product::whereColumn('current_stock','<=','minimum_stock_level')->limit(10)->get(),
            'recent_purchases'=>Purchase::with('supplier')->latest()->limit(5)->get(),'recent_sales'=>Sale::with('customer')->latest()->limit(5)->get()]);
    }
    public function movements(Request $r){return $this->ok(StockMovement::with('product')->when($r->search,fn($q,$s)=>$q->where('reference_number','like',"%$s%"))->latest('movement_date')->paginate(20));}
    public function adjust(Request $r,StockService $s){$d=$r->validate(['product_id'=>'required|exists:products,id','adjustment_date'=>'required|date','adjustment_type'=>'required|in:increase,decrease','quantity'=>'required|numeric|gt:0','reason'=>'required|string|max:255']);
        return $this->ok($s->adjust($d,$r->user()->id),'Stock adjusted successfully.',201);}
    public function payments(Request $r){return $this->ok(Payment::with('supplier','customer')->when($r->payment_type,fn($q,$v)=>$q->where('payment_type',$v))->when($r->date_from,fn($q,$v)=>$q->whereDate('payment_date','>=',$v))->when($r->date_to,fn($q,$v)=>$q->whereDate('payment_date','<=',$v))->latest('payment_date')->paginate(20));}
    public function pay(Request $r){$d=$r->validate(['payment_date'=>'required|date','payment_type'=>'required|in:supplier_payment,customer_payment','supplier_id'=>'nullable|required_if:payment_type,supplier_payment|exists:suppliers,id','customer_id'=>'nullable|required_if:payment_type,customer_payment|exists:customers,id','amount'=>'required|numeric|gt:0','payment_method'=>'required|in:cash,bank_transfer,card,other','reference_number'=>'nullable|string','notes'=>'nullable|string']);
        return DB::transaction(function()use($d,$r){if($d['payment_type']==='supplier_payment'){$party=Supplier::lockForUpdate()->findOrFail($d['supplier_id']);$due=$party->opening_balance+$party->purchases()->where('status','completed')->sum('grand_total')-$party->payments()->where('is_reversed',false)->sum('amount');}
            else{$party=Customer::lockForUpdate()->findOrFail($d['customer_id']);$due=$party->opening_balance+$party->sales()->where('status','completed')->sum('grand_total')-$party->payments()->where('is_reversed',false)->sum('amount');}
            if($d['amount']>$due)throw ValidationException::withMessages(['amount'=>"Payment cannot exceed outstanding amount of {$due}."]);
            $p=Payment::create([...$d,'payment_number'=>Numbers::next(Payment::class,'payment_number','PAY'),'created_by'=>$r->user()->id]);return $this->ok($p,'Payment recorded successfully.',201);});
    }
    public function report(string $type,Request $r){$data=match($type){
        'stock'=>Product::select('*')->selectRaw('current_stock*average_cost as stock_value')->paginate(50),
        'low-stock'=>Product::whereColumn('current_stock','<=','minimum_stock_level')->paginate(50),
        'purchases'=>Purchase::with('supplier')->when($r->date_from,fn($q,$v)=>$q->whereDate('purchase_date','>=',$v))->when($r->date_to,fn($q,$v)=>$q->whereDate('purchase_date','<=',$v))->paginate(50),
        'sales'=>Sale::with('customer')->when($r->date_from,fn($q,$v)=>$q->whereDate('sale_date','>=',$v))->when($r->date_to,fn($q,$v)=>$q->whereDate('sale_date','<=',$v))->paginate(50),
        'profit'=>Sale::with('items')->where('status','completed')->get()->map(fn($s)=>['sale_number'=>$s->sale_number,'sale_date'=>$s->sale_date,'revenue'=>(float)$s->subtotal,'cogs'=>$s->items->sum(fn($i)=>$i->quantity*$i->unit_cost),'gross_profit'=>(float)$s->subtotal-$s->items->sum(fn($i)=>$i->quantity*$i->unit_cost)]),
        default=>throw ValidationException::withMessages(['report'=>'Unknown report.'])}; return $this->ok($data);}
    private function ok($data,$message='Data retrieved successfully.',$status=200){return response()->json(['success'=>true,'message'=>$message,'data'=>$data],$status);}
}
