<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

    public function uploadLogo(Request $request)
    {
        $request->validate(['logo' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048']]);
        $current = Setting::where('key', 'company_logo')->value('value');
        $extension = strtolower($request->file('logo')->extension());
        $path = $request->file('logo')->storeAs('company-logos', 'company-logo-'.now()->format('YmdHis').'.'.$extension, 'public');
        Setting::updateOrCreate(['key' => 'company_logo'], ['value' => $path]);
        if ($current && str_starts_with($current, 'company-logos/')) Storage::disk('public')->delete($current);

        return response()->json(['success' => true, 'message' => 'Company logo updated successfully.', 'data' => Setting::company()]);
    }

    public function removeLogo()
    {
        $current = Setting::where('key', 'company_logo')->value('value');
        Setting::updateOrCreate(['key' => 'company_logo'], ['value' => '']);
        if ($current && str_starts_with($current, 'company-logos/')) Storage::disk('public')->delete($current);

        return response()->json(['success' => true, 'message' => 'Company logo removed.', 'data' => Setting::company()]);
    }
}
