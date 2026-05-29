<?php
// api/stats.php — public, read-only summary counts for the landing page.
// Returns live numbers from the database so the homepage never shows fake data.
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';

try {
    $reports    = (int) $pdo->query("SELECT COUNT(*) FROM waste_reports")->fetchColumn();
    $collected  = (int) $pdo->query("SELECT COUNT(*) FROM waste_reports WHERE status = 'collected'")->fetchColumn();
    $pending    = (int) $pdo->query("SELECT COUNT(*) FROM waste_reports WHERE status IN ('pending','in_progress')")->fetchColumn();
    $collectors = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'collector' AND is_active = 1")->fetchColumn();
    $citizens   = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'citizen' AND is_active = 1")->fetchColumn();
    $areas      = (int) $pdo->query("SELECT COUNT(DISTINCT location_text) FROM waste_reports")->fetchColumn();

    echo json_encode([
        'reports'    => $reports,
        'collected'  => $collected,
        'pending'    => $pending,
        'collectors' => $collectors,
        'citizens'   => $citizens,
        'areas'      => $areas,
    ]);
} catch (PDOException $e) {
    http_response_code(503);
    echo json_encode(['error' => 'stats_unavailable']);
}
