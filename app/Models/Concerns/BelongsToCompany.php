<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use App\Tenancy\CompanyContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);
        static::creating(function ($model) {
            $model->company_id ??= app(CompanyContext::class)->requireId();
        });
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
}
