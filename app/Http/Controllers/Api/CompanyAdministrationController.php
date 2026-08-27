<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{Company,Customer,ProductCategory,ProductSubcategory,Setting,UnitOfMeasurement};
use App\Tenancy\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyAdministrationController extends Controller
{
    public function index(Request $request){$user=$request->user();return $this->ok($user->companies()->orderBy('name')->get(['companies.id','companies.name','companies.code','companies.is_active']));}
    public function store(Request $request){abort_unless($request->user()->is_group_admin,403);$d=$request->validate(['name'=>'required|string|max:120','code'=>['required','alpha_dash','max:40',Rule::unique('companies')->where('group_id',$request->user()->group_id)]]);$company=Company::create([...$d,'group_id'=>$request->user()->group_id,'is_active'=>true]);$company->users()->attach($request->user()->id);app(CompanyContext::class)->set($company);Customer::create(['name'=>'Walk-in Customer','is_walk_in'=>true,'is_active'=>true,'opening_balance'=>0]);foreach(['company_name'=>$company->name,'company_tagline'=>'Procurement, Sales and Inventory','company_address'=>'','company_phone'=>'','company_email'=>'','company_website'=>'','company_tax_number'=>'','company_logo'=>'','currency'=>'PKR'] as $key=>$value)Setting::create(compact('key','value'));foreach([['Piece','pc',0],['Kilogram','kg',3],['Litre','L',3],['Box','box',0],['Pack','pack',0],['Carton','ctn',0],['Ream','ream',0]] as [$name,$symbol,$places])UnitOfMeasurement::create(['name'=>$name,'symbol'=>$symbol,'decimal_places'=>$places,'is_active'=>true]);foreach(['Stationery & Office'=>['Paper Products','Writing Instruments'],'Packaging'=>['Carton Boxes','Tapes & Adhesives'],'Grocery'=>['Rice & Grains','Cooking Oil'],'Cleaning Supplies'=>['Floor Care','Tissues & Paper']] as $name=>$children){$category=ProductCategory::create(['name'=>$name,'is_active'=>true]);foreach($children as $child)ProductSubcategory::create(['product_category_id'=>$category->id,'name'=>$child,'is_active'=>true]);}return $this->ok($company,'Company created.',201);}
    public function update(Request $request,Company $company){abort_unless($request->user()->is_group_admin&&$company->group_id===$request->user()->group_id,403);$d=$request->validate(['name'=>'required|string|max:120','code'=>['required','alpha_dash','max:40',Rule::unique('companies')->where('group_id',$request->user()->group_id)->ignore($company)],'is_active'=>'required|boolean']);$company->update($d);return $this->ok($company,'Company updated.');}
    private function ok($data,$message='Companies retrieved.',$status=200){return response()->json(['success'=>true,'message'=>$message,'data'=>$data],$status);}
}
