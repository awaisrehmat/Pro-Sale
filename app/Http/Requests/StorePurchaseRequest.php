<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use App\Support\TenantRule;
class StorePurchaseRequest extends FormRequest {
    public function authorize(): bool{return true;}
    public function rules():array{return ['purchase_date'=>'required|date','supplier_id'=>['required',TenantRule::exists('suppliers')],'supplier_invoice_number'=>'nullable|string|max:100',
        'payment_method'=>'required|in:cash,bank_transfer,card,other','discount'=>'nullable|numeric|min:0','additional_cost'=>'nullable|numeric|min:0',
        'paid_amount'=>'nullable|numeric|min:0','notes'=>'nullable|string','items'=>'required|array|min:1','items.*.product_id'=>['required','distinct',TenantRule::exists('products')],
        'items.*.quantity'=>'required|numeric|gt:0','items.*.unit_price'=>'required|numeric|min:0','items.*.discount'=>'nullable|numeric|min:0'];}
}
