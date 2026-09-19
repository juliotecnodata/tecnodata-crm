<?php
return [
    'name' => envv('APP_NAME', 'Tecnodata LMS'),
    'env' => envv('APP_ENV', 'production'),
    'debug' => filter_var(envv('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'url' => rtrim((string) envv('APP_URL', ''), '/'),
    'timezone' => envv('APP_TIMEZONE', 'America/Sao_Paulo'),
];
