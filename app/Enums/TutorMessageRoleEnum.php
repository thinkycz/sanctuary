<?php

declare(strict_types=1);

namespace App\Enums;

enum TutorMessageRoleEnum: string
{
    case User = 'user';

    case Assistant = 'assistant';

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
