<?php
namespace Tecnodata\CRM\Services;

use RuntimeException;

final class OmieClient {
    private array $cfg;
    private array $syncCfg;

    public function __construct() {
        $this->cfg = $GLOBALS['config']['omie'];
        $this->syncCfg = $GLOBALS['config']['sync'];
    }

    public function call(string $endpointKey, string $call, array $param): array {
        $url = $this->cfg['endpoints'][$endpointKey] ?? null;
        if (!$url) throw new RuntimeException("Endpoint Omie inválido: {$endpointKey}");

        $payload = [
            'call' => $call,
            'app_key' => $this->cfg['app_key'],
            'app_secret' => $this->cfg['app_secret'],
            'param' => [$param],
        ];

        $attempts = max(1, (int)($this->syncCfg['api_retry_attempts'] ?? 5));
        $baseMs = max(250, (int)($this->syncCfg['api_retry_base_ms'] ?? 1500));
        $lastError = 'Falha desconhecida';

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Accept-Encoding: identity',
                    'Connection: close',
                ],
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_TIMEOUT => (int)($this->cfg['timeout'] ?? 60),
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_ENCODING => 'identity',
            ]);

            $raw = curl_exec($ch);
            $http = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($raw !== false && !$err) {
                $data = json_decode($raw, true);
                if (is_array($data) && $http < 400 && !isset($data['faultstring'])) {
                    return $data;
                }

                $message = is_array($data)
                    ? ($data['faultstring'] ?? $data['message'] ?? "Erro Omie HTTP {$http}")
                    : "Resposta Omie inválida (HTTP {$http})";
                $lastError = $message;

                // A Omie bloqueia temporariamente chamadas idênticas e informa o tempo de espera.
                // Mantemos a mesma página e repetimos somente depois da janela indicada.
                $redundant = str_contains(mb_strtoupper((string)($data['faultcode'] ?? '').' '.$message), 'REDUNDANT');
                if ($redundant && $attempt < $attempts) {
                    $waitSeconds = 40;
                    if (preg_match('/aguarde\s+(\d+)\s+segundos/i', $message, $match)) {
                        $waitSeconds = max(1, (int)$match[1] + 2);
                    }
                    set_time_limit(max(120, $waitSeconds + 60));
                    sleep($waitSeconds);
                    continue;
                }

                // 429 e erros transitórios: espera e tenta novamente.
                if ($http === 429 || $http === 408 || $http >= 500) {
                    if ($attempt < $attempts) {
                        usleep(($baseMs * (2 ** ($attempt - 1))) * 1000);
                        continue;
                    }
                }
                throw new RuntimeException($message);
            }

            $lastError = "Falha cURL Omie: {$err}";
            if ($attempt < $attempts) {
                usleep(($baseMs * (2 ** ($attempt - 1))) * 1000);
                continue;
            }
        }

        throw new RuntimeException($lastError);
    }
}
