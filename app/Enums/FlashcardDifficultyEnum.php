<?php

declare(strict_types=1);

namespace App\Enums;

enum FlashcardDifficultyEnum: string
{
    case Again = 'again';

    case Hard = 'hard';

    case Easy = 'easy';

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
