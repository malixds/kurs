<?php

namespace App\Integrations\DTO;

use Carbon\CarbonInterface;

readonly class WorkProgressRequest
{
    public function __construct(
        public int $companyId,
        public CarbonInterface $from,
        public CarbonInterface $to,
        public array $credentials,
        public array $settings = [],
    ) {}
}
