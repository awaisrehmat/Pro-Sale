<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{Company,Customer,Expense,Payment,Product,Purchase,Sale,StockMovement,Supplier};
use App\Tenancy\CompanyContext;
use App\Services\{PaymentService,StockService};
use App\Support\Numbers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\TenantRule;
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
            'gross_profit_month'=>(float)$sales->sum('subtotal')-(float)$cogs,
            'expenses_today'=>(float)Expense::where('status','posted')->whereDate('expense_date',today())->sum('amount'),
            'expenses_month'=>(float)Expense::where('status','posted')->whereDate('expense_date','>=',$month)->sum('amount'),
            'low_stock'=>Product::whereColumn('current_stock','<=','minimum_stock_level')->limit(10)->get(),
            'supplier_due'=>(float)Purchase::where('status','completed')->sum('due_amount'),'customer_due'=>(float)Sale::where('status','completed')->sum('due_amount'),
            'activity'=>$activity,'recent_purchases'=>Purchase::with('supplier')->latest()->limit(5)->get(),'recent_sales'=>Sale::with('customer')->latest()->limit(5)->get()]);
    }
    public function movements(Request $r){return $this->ok(StockMovement::with('product')->when($r->search,fn($q,$s)=>$q->where(fn($x)=>$x->where('reference_number','like',"%$s%")->orWhere('movement_type','like',"%$s%")->orWhereHas('product',fn($p)=>$p->where('name','like',"%$s%")->orWhere('sku','like',"%$s%"))))->latest('movement_date')->paginate(20));}
    public function adjust(Request $r,StockService $s){$d=$r->validate(['product_id'=>['required',TenantRule::exists('products')],'adjustment_date'=>'required|date','adjustment_type'=>'required|in:increase,decrease','quantity'=>'required|numeric|gt:0','reason'=>'required|string|max:255']);
        return $this->ok($s->adjust($d,$r->user()->id),'Stock adjusted successfully.',201);}
    public function payments(Request $r){return $this->voucherIndex($r,$r->payment_type);}
    public function paymentVouchers(Request $r){return $this->voucherIndex($r,['supplier_payment','expense_payment']);}
    public function receiptVouchers(Request $r){return $this->voucherIndex($r,'customer_payment');}
    public function payment(Payment $payment){return $this->ok($payment->load('supplier','customer','purchase','sale','expense.category'));}
    public function paymentVoucher(Payment $payment){abort_unless($payment->payment_type!=='customer_payment',404);return $this->payment($payment);}
    public function receiptVoucher(Payment $payment){abort_unless($payment->payment_type==='customer_payment',404);return $this->payment($payment);}
    public function pay(Request $r,PaymentService $service){$d=$r->validate(['payment_date'=>'required|date','payment_type'=>'required|in:supplier_payment,customer_payment','supplier_id'=>['nullable','required_if:payment_type,supplier_payment',TenantRule::exists('suppliers')],'customer_id'=>['nullable','required_if:payment_type,customer_payment',TenantRule::exists('customers')],'purchase_id'=>['nullable',TenantRule::exists('purchases')],'sale_id'=>['nullable',TenantRule::exists('sales')],'amount'=>'required|numeric|gt:0','payment_method'=>'required|in:cash,bank_transfer,card,other','reference_number'=>'nullable|string','notes'=>'nullable|string']);
        return $this->ok($service->record($d,$r->user()->id),$d['payment_type']==='customer_payment'?'Receipt voucher created successfully.':'Payment voucher created successfully.',201);}
    public function createPaymentVoucher(Request $r,PaymentService $service){$r->merge(['payment_type'=>'supplier_payment']);return $this->pay($r,$service);}
    public function createReceiptVoucher(Request $r,PaymentService $service){$r->merge(['payment_type'=>'customer_payment']);return $this->pay($r,$service);}
    public function outstanding(Request $r){$d=$r->validate(['payment_type'=>'required|in:supplier_payment,customer_payment','party_id'=>'required|integer']);
        $rows=$d['payment_type']==='supplier_payment'
            ?Purchase::where('supplier_id',$d['party_id'])->where('status','completed')->where('due_amount','>',0)->latest('purchase_date')->get()->map(fn($p)=>['id'=>$p->id,'number'=>$p->purchase_number,'date'=>$p->purchase_date->format('Y-m-d'),'total'=>(float)$p->grand_total,'paid'=>(float)$p->paid_amount,'due'=>(float)$p->due_amount])
            :Sale::where('customer_id',$d['party_id'])->where('status','completed')->where('due_amount','>',0)->latest('sale_date')->get()->map(fn($s)=>['id'=>$s->id,'number'=>$s->sale_number,'date'=>$s->sale_date->format('Y-m-d'),'total'=>(float)$s->grand_total,'paid'=>(float)$s->paid_amount,'due'=>(float)$s->due_amount]);
        return $this->ok($rows);}
    public function paymentContext(Request $r){
        $d=$r->validate(['payment_type'=>'required|in:supplier_payment,customer_payment','party_id'=>'required|integer']);
        $supplier=$d['payment_type']==='supplier_payment';
        $party=$supplier?Supplier::findOrFail($d['party_id']):Customer::findOrFail($d['party_id']);
        $documents=$supplier
            ?Purchase::where('supplier_id',$party->id)->where('status','completed')->orderBy('purchase_date')->orderBy('id')->get()
            :Sale::where('customer_id',$party->id)->where('status','completed')->orderBy('sale_date')->orderBy('id')->get();
        $payments=$party->payments()->where('payment_type',$supplier?'supplier_payment':'customer_payment')
            ->where('is_reversed',false)->orderBy('payment_date')->orderBy('id')->get();
        $opening=(float)$party->opening_balance;
        $billed=(float)$documents->sum('grand_total');
        $settled=(float)$payments->sum('amount');
        $events=collect();
        foreach($documents as $document){
            $events->push(['date'=>($supplier?$document->purchase_date:$document->sale_date)->format('Y-m-d'),'sort'=>0,'id'=>$document->id,
                'type'=>$supplier?'Purchase':'Sale','number'=>$supplier?$document->purchase_number:$document->sale_number,
                'description'=>$supplier?'Supplier invoice added':'Customer invoice raised','increase'=>(float)$document->grand_total,'decrease'=>0,'status'=>ucfirst($document->payment_status)]);
        }
        foreach($payments as $payment){
            $events->push(['date'=>$payment->payment_date->format('Y-m-d'),'sort'=>1,'id'=>$payment->id,
                'type'=>$supplier?'Payment':'Receipt','number'=>$payment->payment_number,
                'description'=>ucwords(str_replace('_',' ',$payment->payment_method)).($payment->reference_number?' · '.$payment->reference_number:''),
                'increase'=>0,'decrease'=>(float)$payment->amount,'status'=>'Posted']);
        }
        $running=$opening;
        $history=$events->sortBy(fn($event)=>sprintf('%s-%d-%010d',$event['date'],$event['sort'],$event['id']))->values()->map(function($event)use(&$running){
            $running=round($running+$event['increase']-$event['decrease'],2);
            return [...$event,'running_balance'=>$running];
        })->reverse()->take(30)->values();
        $outstanding=$documents->where('due_amount','>',0)->sortByDesc(fn($document)=>$supplier?$document->purchase_date:$document->sale_date)->values()->map(fn($document)=>[
            'id'=>$document->id,'number'=>$supplier?$document->purchase_number:$document->sale_number,
            'date'=>($supplier?$document->purchase_date:$document->sale_date)->format('Y-m-d'),'total'=>(float)$document->grand_total,
            'paid'=>(float)$document->paid_amount,'due'=>(float)$document->due_amount]);
        return $this->ok(['party'=>['name'=>$party->name,'phone'=>$party->phone,'email'=>$party->email],
            'summary'=>['direction'=>$supplier?'transfer_out':'transfer_in','direction_label'=>$supplier?'Transfer out':'Transfer in',
                'opening_balance'=>$opening,'billed_total'=>$billed,'settled_total'=>$settled,'outstanding_balance'=>max(0,round($opening+$billed-$settled,2)),
                'open_documents'=>$outstanding->count()],'outstanding'=>$outstanding,'history'=>$history]);
    }

    private function voucherIndex(Request $r,string|array|null $paymentType=null){
        $query=Payment::with('supplier','customer','expense.category')
            ->when($paymentType,fn($q,$type)=>is_array($type)?$q->whereIn('payment_type',$type):$q->where('payment_type',$type))
            ->when($r->search,function($q,$search){$q->where(function($filter)use($search){$filter->where('payment_number','like',"%$search%")
                    ->orWhere('reference_number','like',"%$search%")
                    ->orWhere('payee_name','like',"%$search%")
                ->orWhereHas('supplier',fn($party)=>$party->where('name','like',"%$search%"))
                ->orWhereHas('customer',fn($party)=>$party->where('name','like',"%$search%"));});})
            ->when($r->date_from,fn($q,$v)=>$q->whereDate('payment_date','>=',$v))
            ->when($r->date_to,fn($q,$v)=>$q->whereDate('payment_date','<=',$v))
            ->latest('payment_date')->latest('id');
        return $this->ok($query->paginate(20));
    }
    public function report(string $type,Request $r){
        return $this->ok($this->reportPayload($type,$r));
    }
    public function consolidatedReport(string $type,Request $r){
        abort_unless($r->user()->is_group_admin&&$r->user()->can('reports.consolidated'),403);
        $context=app(CompanyContext::class);$original=$context->company();$payloads=collect();
        try{foreach(Company::where('group_id',$r->user()->group_id)->where('is_active',true)->orderBy('name')->get() as $company){$context->set($company);$payload=$this->reportPayload($type,$r);$payload['rows']=collect($payload['rows']??[])->map(fn($row)=>['company'=>$company->name,...$row])->all();foreach(($payload['details']??[]) as $key=>$detail)$payload['details'][$key]=collect($detail)->map(fn($row)=>['company'=>$company->name,...$row])->all();$payloads->push($payload);}}finally{if($original)$context->set($original);else $context->clear();}
        $summary=[];foreach($payloads as $payload)foreach(($payload['summary']??[]) as $key=>$value)if(is_numeric($value)&&!str_contains($key,'percent'))$summary[$key]=($summary[$key]??0)+(float)$value;
        if(isset($summary['revenue'],$summary['gross_profit']))$summary['margin_percent']=$summary['revenue']>0?round($summary['gross_profit']/$summary['revenue']*100,1):0;
        return $this->ok(['rows'=>$payloads->flatMap(fn($p)=>$p['rows']??[])->values(),'summary'=>$summary,'details'=>['receivables'=>$payloads->flatMap(fn($p)=>$p['details']['receivables']??[])->values(),'payables'=>$payloads->flatMap(fn($p)=>$p['details']['payables']??[])->values()],'period'=>['from'=>$r->date_from,'to'=>$r->date_to,'position_as_of'=>$r->date_to],'consolidated'=>true]);
    }
    private function reportPayload(string $type,Request $r):array{return match($type){
            'stock'=>$this->stockReport($r,false),
            'stock-ledger'=>$this->stockLedgerReport($r),
            'low-stock'=>$this->stockReport($r,true),
            'purchases'=>$this->purchaseReport($r),
            'sales'=>$this->salesReport($r),
            'expenses'=>$this->expenseReport($r),
            'profit'=>$this->profitReport($r),
            'financial'=>$this->financialReport($r),
            default=>throw ValidationException::withMessages(['report'=>'Unknown report.'])};
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

    private function stockLedgerReport(Request $r): array
    {
        $query = StockMovement::with('product:id,name,sku,unit')
            ->when($r->product_id, fn($q,$id)=>$q->where('product_id',$id))
            ->when($r->date_from, fn($q,$date)=>$q->whereDate('movement_date','>=',$date))
            ->when($r->date_to, fn($q,$date)=>$q->whereDate('movement_date','<=',$date))
            ->orderBy('movement_date')->orderBy('id');
        $movements = $query->get();
        $openingByProduct = StockMovement::query()
            ->when($r->product_id, fn($q,$id)=>$q->where('product_id',$id))
            ->when($r->date_from, fn($q,$date)=>$q->whereDate('movement_date','<',$date), fn($q)=>$q->whereRaw('1 = 0'))
            ->selectRaw('product_id, COALESCE(SUM(quantity_in - quantity_out),0) balance')->groupBy('product_id')->pluck('balance','product_id');
        $running = $openingByProduct->map(fn($balance)=>(float)$balance)->all();
        $chronologyIssues = 0;
        $rows = $movements->map(function($movement)use(&$running,&$chronologyIssues){
            $before=(float)($running[$movement->product_id]??0);
            $after=round($before+(float)$movement->quantity_in-(float)$movement->quantity_out,3);
            $running[$movement->product_id]=$after;
            if($after<0)$chronologyIssues++;
            return ['date'=>$movement->movement_date->format('Y-m-d'),'product'=>$movement->product?->name,'sku'=>$movement->product?->sku,
                'unit'=>$movement->product?->unit,'movement'=>ucwords(str_replace('_',' ',$movement->movement_type)),
                'source_document'=>$movement->reference_number ?: 'Manual entry','quantity_in'=>(float)$movement->quantity_in,
                'quantity_out'=>(float)$movement->quantity_out,'stock_before'=>$before,'stock_after'=>$after,
                'balance_status'=>$after<0?'Backdated shortage':'Balanced','unit_cost'=>(float)$movement->unit_cost,'notes'=>$movement->notes];
        });
        $in=(float)$movements->sum('quantity_in');$out=(float)$movements->sum('quantity_out');
        $opening=(float)$openingByProduct->sum();
        return ['rows'=>$rows,'summary'=>['movements'=>$rows->count(),'opening_stock'=>$opening,'total_in'=>$in,
            'total_out'=>$out,'net_change'=>$in-$out,'closing_stock'=>$opening+$in-$out,'chronology_issues'=>$chronologyIssues]];
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

    private function expenseReport(Request $r): array
    {
        $expenses=Expense::with('category','payment')->when($r->date_from,fn($q,$v)=>$q->whereDate('expense_date','>=',$v))
            ->when($r->date_to,fn($q,$v)=>$q->whereDate('expense_date','<=',$v))
            ->when($r->expense_category_id,fn($q,$v)=>$q->where('expense_category_id',$v))
            ->when($r->payment_method,fn($q,$v)=>$q->where('payment_method',$v))
            ->when($r->status,fn($q,$v)=>$q->where('status',$v))->latest('expense_date')->get();
        $rows=$expenses->map(fn($expense)=>['expense_number'=>$expense->expense_number,'date'=>$expense->expense_date->format('Y-m-d'),
            'category'=>$expense->category->name,'paid_to'=>$expense->payee_name,'payment_method'=>ucwords(str_replace('_',' ',$expense->payment_method)),
            'reference'=>$expense->reference_number?:'—','payment_voucher'=>$expense->payment?->payment_number?:'—','amount'=>(float)$expense->amount,'status'=>ucfirst($expense->status)]);
        $posted=$expenses->where('status','posted');
        return ['rows'=>$rows,'summary'=>['expenses'=>$posted->count(),'total_amount'=>(float)$posted->sum('amount'),
            'cash_total'=>(float)$posted->where('payment_method','cash')->sum('amount'),'bank_total'=>(float)$posted->where('payment_method','bank_transfer')->sum('amount'),
            'card_total'=>(float)$posted->where('payment_method','card')->sum('amount'),'cancelled'=>$expenses->where('status','cancelled')->count()]];
    }

    private function profitReport(Request $r):array{
        $rows=Sale::with('items')->where('status','completed')->when($r->date_from,fn($q,$v)=>$q->whereDate('sale_date','>=',$v))
            ->when($r->date_to,fn($q,$v)=>$q->whereDate('sale_date','<=',$v))->when($r->customer_id,fn($q,$v)=>$q->where('customer_id',$v))
            ->latest('sale_date')->get()->map(function($s){$cogs=$s->items->sum(fn($i)=>$i->quantity*$i->unit_cost);$revenue=(float)$s->subtotal;
                return ['sale_number'=>$s->sale_number,'date'=>$s->sale_date->format('Y-m-d'),'revenue'=>$revenue,'cogs'=>round($cogs,2),
                    'gross_profit'=>round($revenue-$cogs,2),'margin_percent'=>$revenue>0?round((($revenue-$cogs)/$revenue)*100,1):0];});
        $revenue=$rows->sum('revenue');$profit=$rows->sum('gross_profit');
        $expenses=(float)Expense::where('status','posted')->when($r->date_from,fn($q,$v)=>$q->whereDate('expense_date','>=',$v))
            ->when($r->date_to,fn($q,$v)=>$q->whereDate('expense_date','<=',$v))->sum('amount');
        return ['rows'=>$rows,'summary'=>['sales'=>$rows->count(),'revenue'=>$revenue,'cogs'=>$rows->sum('cogs'),'gross_profit'=>$profit,
            'operating_expenses'=>$expenses,'net_profit'=>$profit-$expenses,'margin_percent'=>$revenue>0?round(($profit/$revenue)*100,1):0]];
    }

    private function financialReport(Request $r): array
    {
        $from = $r->date_from ?: now()->startOfMonth()->toDateString();
        $to = $r->date_to ?: now()->toDateString();
        $payments = Payment::query()->where('is_reversed', false)
            ->whereBetween('payment_date', [$from, $to])
            ->when($r->payment_method, fn($q,$method)=>$q->where('payment_method',$method))
            ->get();
        $methods = ['cash'=>'Cash','bank_transfer'=>'Bank transfer','card'=>'Card','other'=>'Other'];
        $rows = collect($methods)->map(function($label,$method)use($payments){
            $methodPayments=$payments->where('payment_method',$method);
            $received=(float)$methodPayments->where('payment_type','customer_payment')->sum('amount');
            $paid=(float)$methodPayments->where('payment_type','!=','customer_payment')->sum('amount');
            return ['payment_method'=>$label,'received'=>$received,'paid'=>$paid,'net_movement'=>$received-$paid,
                'receipt_vouchers'=>$methodPayments->where('payment_type','customer_payment')->count(),
                'payment_vouchers'=>$methodPayments->where('payment_type','!=','customer_payment')->count()];
        })->values();

        $receivables = Customer::query()->get()->map(function($customer)use($to){
            $sales=(float)$customer->sales()->where('status','completed')->whereDate('sale_date','<=',$to)->sum('grand_total');
            $received=(float)$customer->payments()->where('payment_type','customer_payment')->where('is_reversed',false)->whereDate('payment_date','<=',$to)->sum('amount');
            return ['party'=>$customer->name,'opening_balance'=>(float)$customer->opening_balance,'transactions'=>$sales,'settled'=>$received,'outstanding'=>max(0,(float)$customer->opening_balance+$sales-$received)];
        })->filter(fn($row)=>$row['outstanding']>0)->sortByDesc('outstanding')->values();
        $payables = Supplier::query()->get()->map(function($supplier)use($to){
            $purchases=(float)$supplier->purchases()->where('status','completed')->whereDate('purchase_date','<=',$to)->sum('grand_total');
            $paid=(float)$supplier->payments()->where('payment_type','supplier_payment')->where('is_reversed',false)->whereDate('payment_date','<=',$to)->sum('amount');
            return ['party'=>$supplier->name,'opening_balance'=>(float)$supplier->opening_balance,'transactions'=>$purchases,'settled'=>$paid,'outstanding'=>max(0,(float)$supplier->opening_balance+$purchases-$paid)];
        })->filter(fn($row)=>$row['outstanding']>0)->sortByDesc('outstanding')->values();
        $received=(float)$rows->sum('received');$paid=(float)$rows->sum('paid');
        $operatingExpenses=(float)$payments->where('payment_type','expense_payment')->sum('amount');
        $supplierPayments=(float)$payments->where('payment_type','supplier_payment')->sum('amount');
        $cash=$rows->firstWhere('payment_method','Cash');
        $bank=$rows->firstWhere('payment_method','Bank transfer');
        return ['rows'=>$rows,'summary'=>['total_received'=>$received,'total_paid'=>$paid,'supplier_payments'=>$supplierPayments,'operating_expenses'=>$operatingExpenses,'net_cash_flow'=>$received-$paid,
            'cash_net_movement'=>$cash['net_movement']??0,'bank_net_movement'=>$bank['net_movement']??0,
            'customer_receivables'=>$receivables->sum('outstanding'),'supplier_payables'=>$payables->sum('outstanding'),
            'net_working_balance'=>$receivables->sum('outstanding')-$payables->sum('outstanding')],
            'details'=>['receivables'=>$receivables->take(10)->values(),'payables'=>$payables->take(10)->values()],
            'period'=>['from'=>$from,'to'=>$to,'position_as_of'=>$to]];
    }
    private function ok($data,$message='Data retrieved successfully.',$status=200){return response()->json(['success'=>true,'message'=>$message,'data'=>$data],$status);}
}
