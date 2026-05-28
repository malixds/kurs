<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class AnalysisPeriodResolver
{
    public static function resolve(?int $periodDays, ?string $from, ?string $to): array
    {
        if ($from !== null && $to !== null) {
            return [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ];
        }

        $days = $periodDays ?? 7;

        if (! in_array((int) $days, [7, 14], true)) {
            throw new InvalidArgumentException('Period must be 7 or 14 days, or provide from/to dates.');
        }

        return [
            now()->subDays($days - 1)->startOfDay(),
            now()->endOfDay(),
        ];
    }

    public static function toArray(CarbonInterface $from, CarbonInterface $to): array
    {
        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'days' => $from->diffInDays($to) + 1,
        ];
    }
}
