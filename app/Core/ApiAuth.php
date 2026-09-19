<?php
namespace Tecnodata\Lms\Core;

final class ApiAuth
{
    private static ?string $traceId = null;
    private static float $startedAt = 0.0;
    private static ?int $requestLogId = null;

    public static function traceId(): string
    {
        if (self::$traceId === null) {
            self::$traceId = 'TDM-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));
        }
        return self::$traceId;
    }

    public static function client(string $requiredScope = ''): array
    {
        self::$startedAt = microtime(true);
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/Bearer\s+(.+)/i', $header, $m)) {
            self::deny(401, 'AUTH_REQUIRED', 'Bearer token obrigatório.');
        }

        $hash = hash('sha256', trim($m[1]));
        $client = Database::fetch(
            "SELECT * FROM api_clients WHERE token_hash=? AND enabled=1 LIMIT 1",
            [$hash]
        );

        if (!$client) {
            self::deny(401, 'INVALID_TOKEN', 'Token inválido.');
        }

        $scopes = json_decode($client['scopes_json'] ?: '[]', true) ?: [];
        if (
            $requiredScope &&
            !in_array('*', $scopes, true) &&
            !in_array($requiredScope, $scopes, true)
        ) {
            self::deny(403, 'SCOPE_DENIED', 'Cliente sem permissão para esta operação.');
        }

        Database::execute(
            "UPDATE api_clients SET last_used_at=? WHERE id=?",
            [Clock::sql(), $client['id']]
        );

        self::startRequestLog((int) $client['id']);
        return $client;
    }

    public static function input(): array
    {
        $raw = file_get_contents('php://input') ?: '{}';
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            self::deny(400, 'INVALID_JSON', 'JSON inválido.');
        }

        return $data;
    }

    public static function json(array $data, int $status = 200): never
    {
        $data['trace_id'] ??= self::traceId();

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function deny(
        int $status,
        string $code,
        string $message,
        array $details = []
    ): never {
        self::json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
                'trace_id' => self::traceId(),
            ],
        ], $status);
    }

    private static function startRequestLog(int $clientId): void
    {
        try {
            Database::execute(
                "INSERT INTO api_requests(api_client_id,trace_id,method,path,ip_address,created_at)
                 VALUES(?,?,?,?,?,?)",
                [
                    $clientId,
                    self::traceId(),
                    (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
                    (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
                    (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                    Clock::sql(),
                ]
            );

            self::$requestLogId = Database::id();

            register_shutdown_function(static function (): void {
                if (!self::$requestLogId) return;
                try {
                    $duration = (int) round((microtime(true) - self::$startedAt) * 1000);
                    Database::execute(
                        "UPDATE api_requests SET status_code=?,duration_ms=? WHERE id=?",
                        [http_response_code(), $duration, self::$requestLogId]
                    );
                } catch (\Throwable) {
                    // Não interrompe a API por falha de telemetria.
                }
            });
        } catch (\Throwable) {
            // Instalações antigas podem ainda não ter api_requests.
        }
    }
}
