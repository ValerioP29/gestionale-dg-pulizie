<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticatedUserResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'cf' => $this->cf ?? null,
            'payroll_code' => $this->payroll_code ?? null,
            'main_site_id' => $this->main_site_id,
            'main_site_name' => optional($this->mainSite)->name,
            'main_site_address' => optional($this->mainSite)->address,
            'role' => $this->role ?? null,
            'active' => (bool) ($this->active ?? true),
        ];
    }
}
