<?php

namespace App\Enums;

enum BurnoutRiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low risk',
            self::Medium => 'Medium risk',
            self::High => 'High risk',
        };
    }
}
