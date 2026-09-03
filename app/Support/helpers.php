<?php
use Tecnodata\CRM\Core\Security;

function money(float|int|string|null $value): string {
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}
function brdate(?string $date): string {
    if (!$date) return '—';
    $ts = strtotime($date); return $ts ? date('d/m/Y', $ts) : '—';
}
function e(?string $s): string { return Security::e($s); }
function redirect(string $path): never {
    header('Location: ' . APP_URL . $path); exit;
}
function days_since(?string $date): ?int {
    if (!$date) return null;
    return (int)(new DateTime($date))->diff(new DateTime('today'))->format('%r%a');
}
function working_days_remaining(?string $date=null): int {
    $d = $date ? new DateTime($date) : new DateTime('today');
    $end = (clone $d)->modify('last day of this month');
    $n = 0;
    for ($x=(clone $d); $x <= $end; $x->modify('+1 day')) {
        if ((int)$x->format('N') <= 5) $n++;
    }
    return max(0, $n);
}
