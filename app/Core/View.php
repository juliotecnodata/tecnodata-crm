<?php
namespace Tecnodata\Lms\Core;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'layout'): void
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require base_path('views/' . $view . '.php');
        $content = ob_get_clean();
        require base_path('views/' . $layout . '.php');
    }
}
