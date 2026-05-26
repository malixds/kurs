<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Hr = 'hr';
    case Manager = 'manager';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Hr => 'HR',
            self::Manager => 'Manager',
        };
    }
}
