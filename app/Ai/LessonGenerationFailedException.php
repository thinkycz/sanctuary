<?php

declare(strict_types=1);

namespace App\Ai;

use RuntimeException;
use Throwable;

final class LessonGenerationFailedException extends RuntimeException
{
    public const string REASON_API_ERROR = 'api_error';

    public const string REASON_INVALID_JSON = 'invalid_json';

    /**
     * Create a custom reason instance.
     */
    public function __construct(string $message, int $code = 0, Throwable|null $previous = null, private readonly string $reason = self::REASON_API_ERROR)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create from a caught throwable.
     */
    public static function fromThrowable(Throwable $e): self
    {
        return new self($e->getMessage(), (int) $e->getCode(), $e, self::REASON_API_ERROR);
    }

    /**
     * Create for invalid JSON output.
     */
    public static function invalidJson(): self
    {
        return new self('The AI returned invalid JSON that could not be validated.', 0, null, self::REASON_INVALID_JSON);
    }

    /**
     * Get the failure reason.
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
