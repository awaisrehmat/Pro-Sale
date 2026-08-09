<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{ProductCategory,ProductSubcategory};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductCategoryController extends Controller {
    public function index(){return $this->ok(ProductCategory::with(['subcategories'=>fn($q)=>$q->orderBy('name')])->withCount('products')->orderBy('name')->get());}
    public function storeCategory(Request $r){$d=$r->validate(['name'=>'required|string|max:100|unique:product_categories,name','is_active'=>'boolean']);return $this->ok(ProductCategory::create($d),'Category created.',201);}
    public function updateCategory(Request $r,ProductCategory $category){$d=$r->validate(['name'=>['required','string','max:100',Rule::unique('product_categories')->ignore($category)],'is_active'=>'boolean']);$category->update($d);return $this->ok($category->load('subcategories'),'Category updated.');}
    public function destroyCategory(ProductCategory $category){if($category->products()->exists()||$category->subcategories()->exists())throw ValidationException::withMessages(['category'=>'This category is in use and cannot be deleted.']);$category->delete();return $this->ok(null,'Category deleted.');}
    public function storeSubcategory(Request $r){$d=$r->validate(['product_category_id'=>'required|exists:product_categories,id','name'=>['required','string','max:100',Rule::unique('product_subcategories')->where(fn($q)=>$q->where('product_category_id',$r->product_category_id))],'is_active'=>'boolean']);return $this->ok(ProductSubcategory::create($d),'Subcategory created.',201);}
    public function updateSubcategory(Request $r,ProductSubcategory $subcategory){$d=$r->validate(['product_category_id'=>'required|exists:product_categories,id','name'=>['required','string','max:100',Rule::unique('product_subcategories')->where(fn($q)=>$q->where('product_category_id',$r->product_category_id))->ignore($subcategory)],'is_active'=>'boolean']);$subcategory->update($d);return $this->ok($subcategory->load('category'),'Subcategory updated.');}
    public function destroySubcategory(ProductSubcategory $subcategory){if($subcategory->products()->exists())throw ValidationException::withMessages(['subcategory'=>'This subcategory is in use and cannot be deleted.']);$subcategory->delete();return $this->ok(null,'Subcategory deleted.');}
    private function ok($data,$message='Categories retrieved.',$status=200){return response()->json(['success'=>true,'message'=>$message,'data'=>$data],$status);}
}
