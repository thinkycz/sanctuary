<?php

declare(strict_types=1);

namespace Tests\Feature\App\Ai;

use App\Ai\Tools\AskClarifyingQuestionsTool;
use Laravel\Ai\Tools\Request;

\test('clarifying questions tool returns correct structure', function (): void {
    $tool = new AskClarifyingQuestionsTool();
    $response = $tool->handle(new Request([
        'question' => 'Which store?',
        'options' => ['Prague', 'Brno'],
        'recommended_option' => 'Prague',
    ]));

    $decoded = \json_decode($response, true);

    static::assertSame('Which store?', $decoded['question']);
    static::assertSame(['Prague', 'Brno'], $decoded['options']);
    static::assertSame('Prague', $decoded['recommended_option']);
});
