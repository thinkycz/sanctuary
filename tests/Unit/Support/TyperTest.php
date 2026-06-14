<?php

declare(strict_types=1);

use Thinkycz\LaravelCore\Support\Typer;

/*
 * Regression tests for two correctness bugs in the core Typer helper:
 *
 *  - `assertNotEmpty` previously had inverted logic: it panicked for
 *    non-empty arrays and silently returned for empty ones.
 *  - `mustParseNullableFloat` guarded on `is_int` instead of `is_float`,
 *    asymmetric with its `parseNullableFloat` sibling.
 */
\describe('Typer::assertNotEmpty', function (): void {
    \test('returns the array when it is not empty')
        ->expect(fn() => Typer::assertNotEmpty(['a', 'b']))
        ->toBe(['a', 'b']);

    \test('panics when the array is empty', function (): void {
        Typer::assertNotEmpty([]);
    })->throws(RuntimeException::class);
});

\describe('Typer::mustParseNullableFloat', function (): void {
    \test('passes through a real float unchanged')
        ->expect(Typer::mustParseNullableFloat(1.5))
        ->toBe(1.5);

    \test('parses a numeric string into a float')
        ->expect(Typer::mustParseNullableFloat('1.5'))
        ->toBe(1.5);

    \test('returns null for null')
        ->expect(Typer::mustParseNullableFloat(null))
        ->toBeNull();

    \test('panics for a non-numeric string', function (): void {
        Typer::mustParseNullableFloat('not-a-float');
    })->throws(AssertionError::class);
});
