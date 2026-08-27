<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{Product,UnitOfMeasurement};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Support\TenantRule;
class ProductController extends Controller {
    private function rules(?Product $p=null):array{return ['name'=>'required|string|max:255','sku'=>['required','string','max:100',TenantRule::unique('products','sku')->ignore($p)],
        'barcode'=>['nullable','string','max:100',TenantRule::unique('products','barcode')->ignore($p)],'description'=>'nullable|string','unit'=>'nullable|required_without:unit_of_measurement_id|string|max:30','unit_of_measurement_id'=>['nullable',TenantRule::exists('units_of_measurement')],
        'product_category_id'=>['nullable',TenantRule::exists('product_categories')],'product_subcategory_id'=>['nullable',TenantRule::exists('product_subcategories')->where(fn($q)=>$q->where('product_category_id',request('product_category_id')))],
        'purchase_price'=>'required|numeric|min:0','sale_price'=>'required|numeric|min:0','minimum_stock_level'=>'required|numeric|min:0','is_active'=>'boolean'];}
    public function index(Request $r){$q=Product::with('category:id,name','subcategory:id,name','unitOfMeasurement:id,name,symbol')->when($r->search,fn($q,$s)=>$q->where(fn($x)=>$x->where('name','like',"%$s%")->orWhere('sku','like',"%$s%")->orWhere('barcode','like',"%$s%")))
        ->when($r->has('active'),fn($q)=>$q->where('is_active',$r->boolean('active')));return $this->ok($q->orderBy($r->get('sort','name'),$r->get('direction','asc'))->paginate($r->integer('per_page',15)));}
    public function store(Request $r){$d=$this->withUnit($r->validate($this->rules()));$opening=(float)($r->validate(['opening_stock'=>'nullable|numeric|min:0'])['opening_stock']??0);$p=Product::create([...$d,'current_stock'=>0,'average_cost'=>$d['purchase_price']]);if($opening>0) app(\App\Services\StockService::class)->adjust(['product_id'=>$p->id,'adjustment_date'=>now()->toDateString(),'adjustment_type'=>'increase','quantity'=>$opening,'reason'=>'Opening stock'],$r->user()->id);return $this->ok($p->fresh(),'Product created successfully.',201);}
    public function show(Product $product){return $this->ok($product->load(['category:id,name','subcategory:id,name','stockMovements'=>fn($q)=>$q->latest('movement_date')]));}
    public function update(Request $r,Product $product){$product->update($this->withUnit($r->validate($this->rules($product))));return $this->ok($product,'Product updated successfully.');}
    public function destroy(Product $product){$product->update(['is_active'=>false]);return $this->ok($product,'Product deactivated successfully.');}
    public function movements(Product $product){return $this->ok($product->stockMovements()->latest('movement_date')->paginate(25));}
    private function ok($data,$message='Products retrieved successfully.',$status=200){return response()->json(compact('data')+['success'=>true,'message'=>$message],$status);}
    private function withUnit(array $data):array{if(!empty($data['unit_of_measurement_id']))$data['unit']=UnitOfMeasurement::findOrFail($data['unit_of_measurement_id'])->symbol;return $data;}
}
