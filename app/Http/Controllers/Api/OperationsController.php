<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{Customer,Payment,Product,Purchase,Sale,StockMovement,Supplier};
use App\Services\{PaymentService,StockService};
use App\Support\Numbers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class OperationsController extends Controller {
    public function dashboard(){
        $month=now()->startOfMonth();$sales=Sale::where('status','completed')->whereDate('sale_date','>=',$month)->get();
        $cogs=\App\Models\SaleItem::whereIn('sale_id',$sales->pluck('id'))->selectRaw('SUM(quantity * unit_cost) total')->value('total')??0;
        $activity=collect(range(6,0))->map(function($days){
            $date=now()->subDays($days);
            return ['date'=>$date->format('M d'),'sales'=>(float)Sale::where('status','completed')->whereDate('sale_date',$date)->sum('grand_total'),
                'purchases'=>(float)Purchase::where('status','completed')->whereDate('purchase_date',$date)->sum('grand_total')];
        });
        return $this->ok(['total_products'=>Product::count(),'total_stock'=>(float)Product::sum('current_stock'),'stock_value'=>(float)Product::selectRaw('SUM(current_stock*average_cost) value')->value('value'),
            'purchases_today'=>(float)Purchase::where('status','completed')->whereDate('purchase_date',today())->sum('grand_total'),'sales_today'=>(float)Sale::where('status','completed')->whereDate('sale_date',today())->sum('grand_total'),
            'purchase_month'=>(float)Purchase::where('status','completed')->whereDate('purchase_date','>=',$month)->sum('grand_total'),'sales_month'=>(float)$sales->sum('grand_total'),
            'gross_profit_month'=>(float)$sales->sum('subtotal')-(float)$cogs,'low_stock'=>Product::whereColumn('current_stock','<=','minimum_stock_level')->limit(10)->get(),
            'supplier_due'=>(float)Purchase::where('status','completed')->sum('due_amount'),'customer_due'=>(float)Sale::where('status','completed')->sum('due_amount'),
            'activity'=>$activity,'recent_purchases'=>Purchase::with('supplier')->latest()->limit(5)->get(),'recent_sales'=>Sale::with('customer')->latest()->limit(5)->get()]);
    }
    public function movements(Request $r){return $this->ok(StockMovement::with('product')->when($r->search,fn($q,$s)=>$q->where(fn($x)=>$x->where('reference_number','like',"%$s%")->orWhere('movement_type','like',"%$s%")->orWhereHas('product',fn($p)=>$p->where('name','like',"%$s%")->orWhere('sku','like',"%$s%"))))->latest('movement_date')->paginate(20));}
    public function adjust(Request $r,StockService $s){$d=$r->validate(['product_id'=>'required|exists:products,id','adjustment_date'=>'required|date','adjustment_type'=>'required|in:increase,decrease','quantity'=>'required|numeric|gt:0','reason'=>'required|string|max:255']);
        return $this->ok($s->adjust($d,$r->user()->id),'Stock adjusted successfully.',201);}
    public function payments(Request $r){return $this->ok(Payment::with('supplier','customer')->when($r->payment_type,fn($q,$v)=>$q->where('payment_type',$v))->when($r->date_from,fn($q,$v)=>$q->whereDate('payment_date','>=',$v))->when($r->date_to,fn($q,$v)=>$q->whereDate('payment_date','<=',$v))->latest('payment_date')->paginate(20));}
    public function payment(Payment $payment){return $this->ok($payment->load('supplier','customer','purchase','sale'));}
    public function pay(Request $r,PaymentService $service){$d=$r->validate(['payment_date'=>'required|date','payment_type'=>'required|in:supplier_payment,customer_payment','supplier_id'=>'nullable|required_if:payment_type,supplier_payment|exists:suppliers,id','customer_id'=>'nullable|required_if:payment_type,customer_payment|exists:customers,id','purchase_id'=>'nullable|exists:purchases,id','sale_id'=>'nullable|exists:sales,id','amount'=>'required|numeric|gt:0','payment_method'=>'required|in:cash,bank_transfer,card,other','reference_number'=>'nullable|string','notes'=>'nullable|string']);
        return $this->ok($service->record($d,$r->user()->id),$d['payment_type']==='customer_payment'?'Receipt voucher created successfully.':'Payment voucher created successfully.',201);}
    public function outstanding(Request $r){$d=$r->validate(['payment_type'=>'required|in:supplier_payment,customer_payment','party_id'=>'required|integer']);
        $rows=$d['payment_type']==='supplier_payment'
            ?Purchase::where('supplier_id',$d['party_id'])->where('status','completed')->where('due_amount','>',0)->latest('purchase_date')->get()->map(fn($p)=>['id'=>$p->id,'number'=>$p->purchase_number,'date'=>$p->purchase_date->format('Y-m-d'),'total'=>(float)$p->grand_total,'paid'=>(float)$p->paid_amount,'due'=>(float)$p->due_amount])
            :Sale::where('customer_id',$d['party_id'])->where('status','completed')->where('due_amount','>',0)->latest('sale_date')->get()->map(fn($s)=>['id'=>$s->id,'number'=>$s->sale_number,'date'=>$s->sale_date->format('Y-m-d'),'total'=>(float)$s->grand_total,'paid'=>(float)$s->paid_amount,'due'=>(float)$s->due_amount]);
        return $this->ok($rows);}
    public function report(string $type,Request $r){
        $payload=match($type){
            'stock'=>$this->stockReport($r,false),
            'low-stock'=>$this->stockReport($r,true),
            'purchases'=>$this->purchaseReport($r),
            'sales'=>$this->salesReport($r),
            'profit'=>$this->profitReport($r),
            default=>throw ValidationException::withMessages(['report'=>'Unknown report.'])};
        return $this->ok($payload);
    }

    private function stockReport(Request $r,bool $lowOnly):array{
        $rows=Product::query()->when($lowOnly,fn($q)=>$q->whereColumn('current_stock','<=','minimum_stock_level'))
            ->when($r->search,fn($q,$v)=>$q->where(fn($x)=>$x->where('name','like',"%$v%")->orWhere('sku','like',"%$v%")))
            ->when($r->status==='in_stock',fn($q)=>$q->whereColumn('current_stock','>','minimum_stock_level'))
            ->when($r->status==='low',fn($q)=>$q->whereColumn('current_stock','<=','minimum_stock_level')->where('current_stock','>',0))
            ->when($r->status==='out',fn($q)=>$q->where('current_stock','<=',0))->orderBy('name')->get()
            ->map(fn($p)=>['product'=>$p->name,'sku'=>$p->sku,'unit'=>$p->unit,'current_stock'=>(float)$p->current_stock,
                'average_cost'=>(float)$p->average_cost,'sale_price'=>(float)$p->sale_price,'stock_value'=>round($p->current_stock*$p->average_cost,2),
                'minimum_stock'=>(float)$p->minimum_stock_level,'stock_status'=>$p->current_stock<=0?'Out of stock':($p->current_stock<=$p->minimum_stock_level?'Low stock':'In stock')]);
        return ['rows'=>$rows,'summary'=>['products'=>$rows->count(),'quantity'=>$rows->sum('current_stock'),'stock_value'=>$rows->sum('stock_value'),
            'low_stock'=>$rows->whereIn('stock_status',['Low stock','Out of stock'])->count()]];
    }

    private function purchaseReport(Request $r):array{
        $rows=Purchase::with('supplier')->when($r->date_from,fn($q,$v)=>$q->whereDate('purchase_date','>=',$v))
            ->when($r->date_to,fn($q,$v)=>$q->whereDate('purchase_date','<=',$v))->when($r->supplier_id,fn($q,$v)=>$q->where('supplier_id',$v))
            ->when($r->payment_status,fn($q,$v)=>$q->where('payment_status',$v))->when($r->status,fn($q,$v)=>$q->where('status',$v))
            ->when($r->product_id,fn($q,$v)=>$q->whereHas('items',fn($x)=>$x->where('product_id',$v)))->latest('purchase_date')->get()
            ->map(fn($p)=>['purchase_number'=>$p->purchase_number,'date'=>$p->purchase_date->format('Y-m-d'),'supplier'=>$p->supplier->name,
                'grand_total'=>(float)$p->grand_total,'paid'=>(float)$p->paid_amount,'due'=>(float)$p->due_amount,'payment_status'=>ucfirst($p->payment_status),'status'=>ucfirst($p->status)]);
        return ['rows'=>$rows,'summary'=>['transactions'=>$rows->count(),'grand_total'=>$rows->sum('grand_total'),'paid'=>$rows->sum('paid'),'due'=>$rows->sum('due')]];
    }

    private function salesReport(Request $r):array{
        $rows=Sale::with('customer')->when($r->date_from,fn($q,$v)=>$q->whereDate('sale_date','>=',$v))
            ->when($r->date_to,fn($q,$v)=>$q->whereDate('sale_date','<=',$v))->when($r->customer_id,fn($q,$v)=>$q->where('customer_id',$v))
            ->when($r->payment_status,fn($q,$v)=>$q->where('payment_status',$v))->when($r->status,fn($q,$v)=>$q->where('status',$v))
            ->when($r->product_id,fn($q,$v)=>$q->whereHas('items',fn($x)=>$x->where('product_id',$v)))->latest('sale_date')->get()
            ->map(fn($s)=>['sale_number'=>$s->sale_number,'date'=>$s->sale_date->format('Y-m-d'),'customer'=>$s->customer->name,
                'grand_total'=>(float)$s->grand_total,'paid'=>(float)$s->paid_amount,'due'=>(float)$s->due_amount,'payment_status'=>ucfirst($s->payment_status),'status'=>ucfirst($s->status)]);
        return ['rows'=>$rows,'summary'=>['transactions'=>$rows->count(),'grand_total'=>$rows->sum('grand_total'),'paid'=>$rows->sum('paid'),'due'=>$rows->sum('due')]];
    }

    private function profitReport(Request $r):array{
        $rows=Sale::with('items')->where('status','completed')->when($r->date_from,fn($q,$v)=>$q->whereDate('sale_date','>=',$v))
            ->when($r->date_to,fn($q,$v)=>$q->whereDate('sale_date','<=',$v))->when($r->customer_id,fn($q,$v)=>$q->where('customer_id',$v))
            ->latest('sale_date')->get()->map(function($s){$cogs=$s->items->sum(fn($i)=>$i->quantity*$i->unit_cost);$revenue=(float)$s->subtotal;
                return ['sale_number'=>$s->sale_number,'date'=>$s->sale_date->format('Y-m-d'),'revenue'=>$revenue,'cogs'=>round($cogs,2),
                    'gross_profit'=>round($revenue-$cogs,2),'margin_percent'=>$revenue>0?round((($revenue-$cogs)/$revenue)*100,1):0];});
        $revenue=$rows->sum('revenue');$profit=$rows->sum('gross_profit');
        return ['rows'=>$rows,'summary'=>['sales'=>$rows->count(),'revenue'=>$revenue,'cogs'=>$rows->sum('cogs'),'gross_profit'=>$profit,
            'margin_percent'=>$revenue>0?round(($profit/$revenue)*100,1):0]];
    }
    private function ok($data,$message='Data retrieved successfully.',$status=200){return response()->json(['success'=>true,'message'=>$message,'data'=>$data],$status);}
}
