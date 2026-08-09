<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class ProductController extends Controller {
    private function rules(?Product $p=null):array{return ['name'=>'required|string|max:255','sku'=>'required|string|max:100|unique:products,sku,'.($p?->id??'NULL'),
        'barcode'=>'nullable|string|max:100|unique:products,barcode,'.($p?->id??'NULL'),'description'=>'nullable|string','unit'=>'required|string|max:30',
        'product_category_id'=>'nullable|exists:product_categories,id','product_subcategory_id'=>['nullable',Rule::exists('product_subcategories','id')->where(fn($q)=>$q->where('product_category_id',request('product_category_id')))],
        'purchase_price'=>'required|numeric|min:0','sale_price'=>'required|numeric|min:0','minimum_stock_level'=>'required|numeric|min:0','is_active'=>'boolean'];}
    public function index(Request $r){$q=Product::with('category:id,name','subcategory:id,name')->when($r->search,fn($q,$s)=>$q->where(fn($x)=>$x->where('name','like',"%$s%")->orWhere('sku','like',"%$s%")->orWhere('barcode','like',"%$s%")))
        ->when($r->has('active'),fn($q)=>$q->where('is_active',$r->boolean('active')));return $this->ok($q->orderBy($r->get('sort','name'),$r->get('direction','asc'))->paginate($r->integer('per_page',15)));}
    public function store(Request $r){$d=$r->validate($this->rules());$opening=(float)($r->validate(['opening_stock'=>'nullable|numeric|min:0'])['opening_stock']??0);$p=Product::create([...$d,'current_stock'=>0,'average_cost'=>$d['purchase_price']]);if($opening>0) app(\App\Services\StockService::class)->adjust(['product_id'=>$p->id,'adjustment_date'=>now()->toDateString(),'adjustment_type'=>'increase','quantity'=>$opening,'reason'=>'Opening stock'],$r->user()->id);return $this->ok($p->fresh(),'Product created successfully.',201);}
    public function show(Product $product){return $this->ok($product->load(['category:id,name','subcategory:id,name','stockMovements'=>fn($q)=>$q->latest('movement_date')]));}
    public function update(Request $r,Product $product){$product->update($r->validate($this->rules($product)));return $this->ok($product,'Product updated successfully.');}
    public function destroy(Product $product){$product->update(['is_active'=>false]);return $this->ok($product,'Product deactivated successfully.');}
    public function movements(Product $product){return $this->ok($product->stockMovements()->latest('movement_date')->paginate(25));}
    private function ok($data,$message='Products retrieved successfully.',$status=200){return response()->json(compact('data')+['success'=>true,'message'=>$message],$status);}
}
