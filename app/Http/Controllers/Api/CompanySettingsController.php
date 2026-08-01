<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanySettingsController extends Controller
{
    public function show()
    {
        return response()->json(['success' => true, 'message' => 'Company settings retrieved.', 'data' => Setting::company()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'company_tagline' => ['nullable', 'string', 'max:160'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_email' => ['nullable', 'email', 'max:120'],
            'company_website' => ['nullable', 'string', 'max:160'],
            'company_tax_number' => ['nullable', 'string', 'max:80'],
            'currency' => ['required', 'string', 'max:10'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value ?? '']);
            }
        });

        return response()->json(['success' => true, 'message' => 'Company details updated successfully.', 'data' => Setting::company()]);
    }
}
