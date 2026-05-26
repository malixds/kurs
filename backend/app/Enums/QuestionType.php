<?php

namespace App\Enums;

enum QuestionType: string
{
    case Scale = 'scale';
    case Text = 'text';
    case Boolean = 'boolean';

    public function isScorable(): bool
    {
        return $this !== self::Text;
    }
}
