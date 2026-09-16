<?php

declare(strict_types=1);

namespace App;

final class Config
{
    /** @return array<string, string> */
    public static function env(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $values = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim($value, " \t\n\r\0\x0B\"");
        }

        return $values;
    }

    /** @return array<string, string> */
    public static function all(string $path): array
    {
        $values = self::env($path);

        foreach (['SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_PASSWORD', 'SMTP_ENCRYPTION', 'SMTP_FROM', 'SMTP_FROM_NAME'] as $key) {
            $value = getenv($key);
            if ($value === false || $value === null) {
                $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
            }

            if (is_string($value) && $value !== '') {
                $values[$key] = $value;
            }
        }

        return $values;
    }
}
