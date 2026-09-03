<?php
namespace Tecnodata\CRM\Core;

final class Security {
    public static function csrf(): string {
        if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf'];
    }
    public static function verifyCsrf(?string $token): bool {
        return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
    }
    public static function e(?string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}
