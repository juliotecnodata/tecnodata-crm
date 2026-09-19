<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;

final class ReportController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $byCourse=Database::all(
            "SELECT c.id,c.code,c.name,
                    COUNT(e.id) enrollments,
                    SUM(e.status='active') active_count,
                    SUM(e.status='completed') completed_count,
                    ROUND(AVG(e.progress_percent),2) avg_progress,
                    ROUND(AVG(e.final_score),2) avg_score
               FROM courses c
               LEFT JOIN enrollments e ON e.course_id=c.id
              WHERE c.status<>'archived'
              GROUP BY c.id
              ORDER BY enrollments DESC,c.name"
        );

        $byDay=Database::all(
            "SELECT DATE(created_at) day,COUNT(*) total
               FROM enrollments
              WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 30 DAY)
              GROUP BY DATE(created_at)
              ORDER BY day"
        );

        View::render('reports/index',[
            'byCourse'=>$byCourse,
            'byDay'=>$byDay,
        ]);
    }
}
