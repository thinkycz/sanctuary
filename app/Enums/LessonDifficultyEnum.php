<?php

declare(strict_types=1);

namespace App\Enums;

enum LessonDifficultyEnum: string
{
    case Beginner = 'beginner';

    case Intermediate = 'intermediate';

    case Advanced = 'advanced';

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
