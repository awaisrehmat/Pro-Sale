<?php

namespace App\Tenancy;

use App\Models\Company;
use RuntimeException;

class CompanyContext
{
    private ?Company $company = null;

    public function set(Company $company): void { $this->company = $company; }
    public function clear(): void { $this->company = null; }
    public function company(): ?Company { return $this->company; }
    public function id(): ?int { return $this->company?->id; }
    public function requireId(): int { return $this->id() ?? throw new RuntimeException('No company has been selected.'); }
}
