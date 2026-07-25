<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\{StorePurchaseRequest,StoreSaleRequest};
use App\Models\{Purchase,Sale};
use App\Services\{PurchaseService,SaleService};
use Illuminate\Http\Request;
class TransactionController extends Controller {
    public function purchases(Request $r){return $this->ok(Purchase::with('supplier')->when($r->search,fn($q,$s)=>$q->where('purchase_number','like',"%$s%"))->latest('purchase_date')->paginate(15));}
    public function createPurchase(StorePurchaseRequest $r,PurchaseService $s){return $this->ok($s->create($r->validated(),$r->user()->id),'Purchase created successfully.',201);}
    public function purchase(Purchase $purchase){return $this->ok($purchase->load('supplier','items.product','payments'));}
    public function cancelPurchase(Request $r,Purchase $purchase,PurchaseService $s){return $this->ok($s->cancel($purchase,$r->user()->id),'Purchase cancelled successfully.');}
    public function sales(Request $r){return $this->ok(Sale::with('customer')->when($r->search,fn($q,$s)=>$q->where('sale_number','like',"%$s%"))->latest('sale_date')->paginate(15));}
    public function createSale(StoreSaleRequest $r,SaleService $s){return $this->ok($s->create($r->validated(),$r->user()->id),'Sale created successfully.',201);}
    public function sale(Sale $sale){return $this->ok($sale->load('customer','items.product','payments'));}
    public function cancelSale(Request $r,Sale $sale,SaleService $s){return $this->ok($s->cancel($sale,$r->user()->id),'Sale cancelled successfully.');}
    private function ok($data,$message='Records retrieved successfully.',$status=200){return response()->json(['success'=>true,'message'=>$message,'data'=>$data],$status);}
}
