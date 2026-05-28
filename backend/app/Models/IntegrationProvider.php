<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntegrationProvider extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'auth_type',
        'config_schema',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'config_schema' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function companyIntegrations(): HasMany
    {
        return $this->hasMany(CompanyIntegration::class, 'provider_slug', 'slug');
    }
}
