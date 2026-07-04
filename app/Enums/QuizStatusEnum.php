<?php

declare(strict_types=1);

namespace App\Enums;

enum QuizStatusEnum: string
{
    case NotStarted = 'not_started';

    case InProgress = 'in_progress';

    case Completed = 'completed';

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
