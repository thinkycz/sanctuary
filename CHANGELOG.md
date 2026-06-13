# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Pre-release boilerplate. Per-release changelog entries land under
their own `## [X.Y.Z]` heading at tag time.

The boilerplate currently ships:

- Laravel 13 on PHP 8.3, Inertia 3, Vue 3 (Composition API) + TypeScript,
  Tailwind 4, Vite.
- `packages/thinkycz/laravel-core` as a local path repository
  (helpers, guards, validation, scaffolding stubs, CRUD commands).
- Authentication via the core `database_token` guard over HTTP-only
  cookies. MustVerifyEmail, password reset, and email verification flows
  are wired end-to-end.
- 103 Pest test cases (Feature + Architecture + Unit) and 17 Playwright
  E2E cases; both run under `make check` and produce 2091+ assertions
  with 0 risky on the i18n + settings work.
- 4 Vitest unit tests for the i18n parity helper.
- Architecture-level enforcement: no inline FQNs in `routes/*`, no
  `ValidationException::withMessages` in `app/`, no `$request->session()->flash`
  in `app/` (use `Inertia::flash()` instead), no `env()` calls, multi-step
  persistence wrapped in `DB::transaction(...)`, every Web+Api controller
  has a matching feature test.
- PHPStan at `level: max` with `treatPhpDocTypesAsCertain: true`; Larastan's
  Eloquent magic builder exemption is configured in `phpstan.neon`.
- Three-locale i18n (en/cs/sk) parity enforced by
  `tests/Unit/I18nParityTest.php`.
- E2E throttling bypass via `E2E_DISABLE_THROTTLE=true` in the Playwright
  `webServer.env`.

## [0.1.0] - 2026-06-07

Initial snapshot captured in `docs/verification/baseline-2026-06-07.md`.
14 tests / 45 assertions, Inertia 2 → 3 migration, PHP 8.3, Laravel 13.
