<?php

namespace App\Models\Scopes;

use App\Tenancy\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = app(CompanyContext::class)->id();
        $builder->where($model->qualifyColumn('company_id'), $companyId ?? -1);
    }
}
