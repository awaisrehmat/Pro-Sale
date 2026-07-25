<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{Customer,Supplier};
use Illuminate\Http\Request;
class PartyController extends Controller {
    public function suppliers(Request $r){return $this->ok(Supplier::query()->when($r->search,fn($q,$s)=>$q->where(fn($x)=>$x->where('name','like',"%$s%")->orWhere('phone','like',"%$s%")))->orderBy('name')->paginate(15));}
    public function storeSupplier(Request $r){return $this->ok(Supplier::create($r->validate($this->supplierRules())), 'Supplier created successfully.',201);}
    public function supplier(Supplier $supplier){return $this->ok($supplier->load('purchases','payments'));}
    public function updateSupplier(Request $r,Supplier $supplier){$supplier->update($r->validate($this->supplierRules()));return $this->ok($supplier,'Supplier updated successfully.');}
    public function supplierLedger(Supplier $supplier){$p=$supplier->purchases()->where('status','completed')->sum('grand_total');$paid=$supplier->payments()->where('is_reversed',false)->sum('amount');
        return $this->ok(['party'=>$supplier,'opening_balance'=>(float)$supplier->opening_balance,'purchases'=>(float)$p,'payments'=>(float)$paid,'outstanding'=>(float)$supplier->opening_balance+$p-$paid,'transactions'=>['purchases'=>$supplier->purchases()->latest()->get(),'payments'=>$supplier->payments()->latest()->get()]]);}
    public function customers(Request $r){return $this->ok(Customer::query()->when($r->search,fn($q,$s)=>$q->where(fn($x)=>$x->where('name','like',"%$s%")->orWhere('phone','like',"%$s%")))->orderBy('name')->paginate(15));}
    public function storeCustomer(Request $r){return $this->ok(Customer::create($r->validate($this->customerRules())),'Customer created successfully.',201);}
    public function customer(Customer $customer){return $this->ok($customer->load('sales','payments'));}
    public function updateCustomer(Request $r,Customer $customer){$customer->update($r->validate($this->customerRules()));return $this->ok($customer,'Customer updated successfully.');}
    public function customerLedger(Customer $customer){$s=$customer->sales()->where('status','completed')->sum('grand_total');$paid=$customer->payments()->where('is_reversed',false)->sum('amount');
        return $this->ok(['party'=>$customer,'opening_balance'=>(float)$customer->opening_balance,'sales'=>(float)$s,'payments'=>(float)$paid,'outstanding'=>(float)$customer->opening_balance+$s-$paid,'transactions'=>['sales'=>$customer->sales()->latest()->get(),'payments'=>$customer->payments()->latest()->get()]]);}
    private function supplierRules(){return ['name'=>'required|string|max:255','contact_person'=>'nullable|string|max:255','phone'=>'nullable|string|max:50','email'=>'nullable|email','address'=>'nullable|string','opening_balance'=>'nullable|numeric|min:0','notes'=>'nullable|string','is_active'=>'boolean'];}
    private function customerRules(){return ['name'=>'required|string|max:255','phone'=>'nullable|string|max:50','email'=>'nullable|email','address'=>'nullable|string','opening_balance'=>'nullable|numeric|min:0','notes'=>'nullable|string','is_active'=>'boolean'];}
    private function ok($data,$message='Records retrieved successfully.',$status=200){return response()->json(['success'=>true,'message'=>$message,'data'=>$data],$status);}
}
