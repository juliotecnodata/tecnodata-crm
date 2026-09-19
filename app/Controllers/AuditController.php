<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;

final class AuditController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $events=Database::all(
            "SELECT a.*,u.name user_name
               FROM audit_events a
               LEFT JOIN users u ON u.id=a.user_id
              ORDER BY a.id DESC
              LIMIT 1000"
        );

        View::render('audit/index',['events'=>$events]);
    }
}
