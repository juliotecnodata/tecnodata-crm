<?php
return [
    'app' => [
        'name' => 'Inteligência de Carteira',
        'company' => 'Tecnodata Educacional',
        'timezone' => 'America/Sao_Paulo',
        'environment' => 'auto',
        'local_url' => 'http://localhost/tecnodata-crm/public',
        'production_url' => 'https://tecnodataeducacional.com.br/crm',
        'session_name' => 'tecnodata_crm',
        'debug' => true,
    ],
    'database' => [
        'local' => [
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'crm_tecnodata',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
        ],
        'production' => [
            'host' => 'SEU_HOST_MYSQL',
            'port' => 3306,
            'database' => 'SEU_BANCO',
            'username' => 'SEU_USUARIO',
            'password' => 'SUA_SENHA',
            'charset' => 'utf8mb4',
        ],
    ],
    'omie' => [
        'app_key' => 'SUA_APP_KEY',
        'app_secret' => 'SEU_APP_SECRET',
    ],
    'installer' => [
        'enabled' => false,
        'token' => 'ALTERE_ESTE_TOKEN',
        'admin' => [
            'name' => 'Administrador',
            'email' => 'admin@exemplo.com',
            'password' => 'ALTERE_ESTA_SENHA',
        ],
    ],
];
