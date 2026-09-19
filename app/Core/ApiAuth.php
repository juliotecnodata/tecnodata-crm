<?php
namespace Tecnodata\Lms\Core;

final class ApiAuth
{
    public static function client(string $requiredScope = ''): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\s+(.+)/i', $header, $m)) {
            self::deny(401, 'AUTH_REQUIRED', 'Bearer token obrigatório.');
        }
        $hash = hash('sha256', trim($m[1]));
        $client = Database::fetch("SELECT * FROM api_clients WHERE token_hash=? AND enabled=1 LIMIT 1", [$hash]);
        if (!$client) self::deny(401, 'INVALID_TOKEN', 'Token inválido.');
        $scopes = json_decode($client['scopes_json'] ?: '[]', true) ?: [];
        if ($requiredScope && !in_array('*', $scopes, true) && !in_array($requiredScope, $scopes, true)) {
            self::deny(403, 'SCOPE_DENIED', 'Cliente sem permissão para esta operação.');
        }
        Database::execute("UPDATE api_clients SET last_used_at=? WHERE id=?", [Clock::sql(), $client['id']]);
        return $client;
    }

    public static function input(): array
    {
        $data = json_decode(file_get_contents('php://input') ?: '{}', true);
        if (!is_array($data)) self::deny(400, 'INVALID_JSON', 'JSON inválido.');
        return $data;
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function deny(int $status, string $code, string $message, array $details = []): never
    {
        self::json(['success'=>false,'error'=>['code'=>$code,'message'=>$message,'details'=>$details]], $status);
    }
}
