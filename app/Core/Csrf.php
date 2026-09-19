<?php
namespace Tecnodata\Lms\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function verify(): void
    {
        $given = (string) ($_POST['_csrf'] ?? '');
        if (!hash_equals((string) ($_SESSION['_csrf'] ?? ''), $given)) {
            http_response_code(419);
            exit('Sessão expirada. Atualize a página e tente novamente.');
        }
    }
}
