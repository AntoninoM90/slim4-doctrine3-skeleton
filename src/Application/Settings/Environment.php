<?php

declare(strict_types=1);

namespace App\Application\Settings;

/**
 * Provides read access to environment variables with default values.
 */
final class Environment
{
    /**
     * Returns the value of an environment variable, or the given default
     * when the variable is not set or is set to an empty string.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        return $value === false || $value === '' ? $default : $value;
    }
}
