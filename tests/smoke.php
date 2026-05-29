<?php

require_once __DIR__ . '/bootstrap.php';

$pdo = createTestPdo();
$ids = seedTestData($pdo);

$report = loadCitizenReport($pdo, $ids['citizen_one_report'], 1);
assertTrue(is_array($report), 'Citizen should see own report.');
assertSame('Citizen one report', $report['description'], 'Citizen report description should match.');
assertSame('Main street', $report['location_text'], 'Citizen report location should match.');

$update = updateCollectorReportStatus($pdo, $ids['citizen_one_report'], 10, 'in_progress', 'Picked up near gate');
assertTrue($update['success'], 'Assigned collector should be able to update status.');
assertSame(1, $update['updated_rows'], 'Assigned collector update should affect one row.');

$updatedReport = loadCitizenReport($pdo, $ids['citizen_one_report'], 1);
assertSame('in_progress', $updatedReport['status'], 'Status should update after collector action.');

$logCount = (int)$pdo->query("SELECT COUNT(*) FROM collection_logs WHERE report_id = 1 AND collector_id = 10")->fetchColumn();
assertSame(1, $logCount, 'Collector note should be saved in collection_logs.');

$logNotes = $pdo->query("SELECT notes FROM collection_logs WHERE report_id = 1 AND collector_id = 10")->fetchColumn();
assertSame('Picked up near gate', $logNotes, 'Typed note should be stored exactly.');

echo "Smoke tests passed\n";
