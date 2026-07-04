<?php

declare(strict_types=1);

namespace App\Enums;

enum TermDifficultyEnum: string
{
    case Unknown = 'unknown';

    case Learning = 'learning';

    case Mastered = 'mastered';

    /**
     * Get possible values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return \array_column(self::cases(), 'value');
    }
}
