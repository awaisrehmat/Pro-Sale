<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function company(): array
    {
        $defaults = [
            'company_name' => 'Stock Manager',
            'company_tagline' => 'Procurement, Sales and Inventory',
            'company_address' => '',
            'company_phone' => '',
            'company_email' => '',
            'company_website' => '',
            'company_tax_number' => '',
            'currency' => 'PKR',
        ];

        return array_replace($defaults, static::whereIn('key', array_keys($defaults))->pluck('value', 'key')->all());
    }
}
