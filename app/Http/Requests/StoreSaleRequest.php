<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use App\Support\TenantRule;
class StoreSaleRequest extends FormRequest {
    public function authorize(): bool{return true;}
    public function rules():array{return ['sale_date'=>'required|date','customer_id'=>['required',TenantRule::exists('customers')],'payment_method'=>'required|in:cash,bank_transfer,card,other',
        'discount'=>'nullable|numeric|min:0','tax'=>'nullable|numeric|min:0','paid_amount'=>'nullable|numeric|min:0','notes'=>'nullable|string',
        'items'=>'required|array|min:1','items.*.product_id'=>['required','distinct',TenantRule::exists('products')],'items.*.quantity'=>'required|numeric|gt:0',
        'items.*.unit_price'=>'required|numeric|min:0','items.*.discount'=>'nullable|numeric|min:0'];}
}
