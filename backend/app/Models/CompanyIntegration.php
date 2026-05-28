<?php

namespace App\Models;

use App\Integrations\Enums\IntegrationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyIntegration extends Model
{
    protected $fillable = [
        'company_id',
        'provider_slug',
        'status',
        'credentials',
        'settings',
        'last_sync_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'settings' => 'array',
            'status' => IntegrationStatus::class,
            'last_sync_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(IntegrationProvider::class, 'provider_slug', 'slug');
    }

    public function employeeIdentities(): HasMany
    {
        return $this->hasMany(EmployeeIntegrationIdentity::class);
    }

    public function isConnected(): bool
    {
        return $this->status === IntegrationStatus::Connected;
    }
}
