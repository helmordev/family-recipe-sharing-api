<?php

declare(strict_types=1);

namespace App\Enums;

enum RecipeVisibility: string
{
    case PUBLIC = 'public';
    case FAMILY = 'family';
    case PRIVATE = 'private';

    public function label(): string
    {
        return match ($this) {
            self::PUBLIC => 'Public',
            self::FAMILY => 'Family',
            self::PRIVATE => 'Private',
        };
    }
}
