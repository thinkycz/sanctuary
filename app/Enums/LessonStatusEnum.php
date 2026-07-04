<?php

declare(strict_types=1);

namespace App\Enums;

enum LessonStatusEnum: string
{
    case Pending = 'pending';

    case Generating = 'generating';

    case Ready = 'ready';

    case Failed = 'failed';

    /**
     * Get possible values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return \array_column(self::cases(), 'value');
    }

    /**
     * Statuses that indicate the lesson is still being processed.
     *
     * @return array<int, string>
     */
    public static function activeValues(): array
    {
        return [self::Pending->value, self::Generating->value];
    }
}
