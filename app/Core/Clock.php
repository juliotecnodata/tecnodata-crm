<?php
namespace Tecnodata\Lms\Core;

use DateTimeImmutable;
use DateTimeZone;

final class Clock
{
    private static DateTimeZone $tz;

    public static function boot(): void
    {
        self::$tz = new DateTimeZone((string) envv('APP_TIMEZONE', 'America/Sao_Paulo'));
    }

    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', self::$tz);
    }

    public static function sql(): string
    {
        return self::now()->format('Y-m-d H:i:s');
    }

    public static function iso(?string $sqlDate): ?string
    {
        if (!$sqlDate) return null;
        return (new DateTimeImmutable($sqlDate, self::$tz))->format(DATE_ATOM);
    }
}
