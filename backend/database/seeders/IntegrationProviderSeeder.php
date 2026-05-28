<?php

namespace Database\Seeders;

use App\Integrations\IntegrationManager;
use Illuminate\Database\Seeder;

class IntegrationProviderSeeder extends Seeder
{
    public function run(): void
    {
        app(IntegrationManager::class)->seedProviders();
    }
}
