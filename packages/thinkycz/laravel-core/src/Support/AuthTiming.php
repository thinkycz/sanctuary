<?php

declare(strict_types=1);

namespace Thinkycz\LaravelCore\Support;

/**
 * Timing-equalization helper for credential checks.
 *
 * When a login attempt references a user that does not exist, the
 * password-verification step is normally skipped, which leaks the
 * existence of an account through response timing. Running the hasher
 * against a real, precomputed bcrypt digest keeps the failure path's
 * cost identical to the success path.
 *
 * The hash below is a valid bcrypt digest of a random throwaway value
 * ("timing-equalization-dummy"); it is never used to authenticate.
 */
final class AuthTiming
{
    /**
     * Precomputed bcrypt digest used solely to equalize the timing of
     * the password check when no user was found.
     */
    public const string DUMMY_HASH = '$2y$10$jyYoB.c3LkgYlSmTMBQCQuzCPKVdMtBf04mgi2euFYZhQfxe/OrQu';

    /**
     * Run a dummy password check that mirrors the cost of a real
     * verification, then return false.
     */
    public static function dummyPasswordCheck(string $password): bool
    {
        return Resolver::resolveHasher()->check($password, self::DUMMY_HASH);
    }
}
