<?php
/**
 * TECNODATA CRM — CONFIGURAÇÃO CENTRAL
 *
 * Este é o único arquivo que você precisa editar para credenciais e ambiente.
 * Em produção, mantenha esta pasta protegida por .htaccess (já incluso).
 */
return [
    'app' => [
        'name' => 'Inteligência de Carteira',
        'company' => 'Tecnodata Educacional',
        'timezone' => 'America/Sao_Paulo',
        'environment' => 'auto', // auto | local | production
        'local_url' => 'http://localhost/tecnodata-crm/public',
        'production_url' => 'https://tecnodataeducacional.com.br/crm',
        'session_name' => 'tecnodata_crm',
        'debug' => true, // altere para false em produção
    ],

    'database' => [
        'local' => [
            'host' => 'srv1530.hstgr.io',
            'port' => 3306,
            'database' => 'u695906402_crm_tecnodata',
            'username' => 'u695906402_crm_tecnodata',
            'password' => '#@Tecno623#@',
            'charset' => 'utf8mb4',
        ],
        'production' => [
            'host' => 'SEU_HOST_MYSQL_HOSTINGER',
            'port' => 3306,
            'database' => 'SEU_BANCO',
            'username' => 'SEU_USUARIO',
            'password' => 'SUA_SENHA',
            'charset' => 'utf8mb4',
        ],
    ],

    'omie' => [
        'app_key' => '316403016930',
        'app_secret' => 'a03b094aa34becddac4e4b537c0d3915',
        'timeout' => 60,
        'per_page' => 100,
        'endpoints' => [
            'clientes' => 'https://app.omie.com.br/api/v1/geral/clientes/',
            'vendedores' => 'https://app.omie.com.br/api/v1/geral/vendedores/',
            'pedidos' => 'https://app.omie.com.br/api/v1/produtos/pedido/',
            'ordens_servico' => 'https://app.omie.com.br/api/v1/servicos/os/',
            'etapas_pedido' => 'https://app.omie.com.br/api/v1/produtos/etapafat/',
            'financeiro' => 'https://app.omie.com.br/api/v1/financas/mf/',
            'contas_correntes' => 'https://app.omie.com.br/api/v1/geral/contacorrente/',
        ],
        // Regra inicial para "realizado": considerar pedidos sincronizados do mês
        // exceto cancelados/devolvidos. Pode ser refinada após validar os status reais da conta.
        'ignored_order_statuses' => ['CANCELADO', 'CANCELADA', 'DEVOLVIDO', 'DEVOLVIDA', 'DENEGADO'],
        // Na operação atual da Omie, a etapa 00 é Proposta/Orçamento e não compõe meta.
        'order_budget_stage_codes' => ['00'],
        'history_start' => date('Y-m-01',strtotime('first day of last month')),
    ],

    'sync' => [
        // Nunca sincronizamos durante a navegação normal.
        // A sincronização modular processa UMA página por requisição HTTP e pode ser retomada.
        'recommended_times' => ['06:00', '12:00', '18:00', '23:00'],
        'lock_minutes' => 15,
        'request_pause_ms' => 900,      // pausa no navegador entre páginas
        'api_retry_attempts' => 5,      // retry para 429/5xx
        'api_retry_base_ms' => 1500,    // backoff: 1.5s, 3s, 6s...
        'metrics_batch_size' => 100,    // clientes recalculados por etapa
        'page_size' => 100,             // máximo atual da Omie; reduz a carga inicial pela metade
        'financial_history_years' => 3, // carga inicial: somente os últimos 3 anos
        'commercial_history_start' => date('Y-m-01',strtotime('first day of last month')), // pedidos e OS: mês anterior + atual
        'service_history_start' => date('Y-m-01',strtotime('first day of last month')),
        'service_page_size' => 100, // limite efetivo observado na API de Serviços da Omie
        'financial_overlap_days' => 2,  // margem para inclusões/alterações entre sincronizações
    ],

    'alerts' => [
        // Alertas consultam somente o banco local; nunca disparam chamadas à Omie.
        'poll_seconds' => 60,
        'default_pre_alert_minutes' => 10,
        'default_repeat_after_minutes' => 15,
        'default_sound_enabled' => true,
        'default_browser_enabled' => true,
        'default_volume' => 70,
        'max_events_per_poll' => 10,
    ],

    'commercial' => [
        'attention_days' => 61,
        'reactivate_days' => 181,
        'post_contact_sale_window_days' => 30,
    ],

    'installer' => [
        'enabled' => false,
        'token' => 'xxx123456',
        'admin' => [
            'name' => 'Julio',
            'email' => 'julio@tecnodatacfc.com.br',
            'password' => 'larinha83',
        ],
    ],
];
