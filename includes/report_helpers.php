<?php

function reportStatusBadgeClass(string $status): string
{
  return 'badge ' . $status;
}

function reportStatusLabel(string $status): string
{
  switch ($status) {
    case 'pending':
      return 'Pending';
    case 'in_progress':
      return 'In Progress';
    case 'collected':
      return 'Collected';
    case 'rejected':
      return 'Rejected';
    default:
      return ucfirst(str_replace('_', ' ', $status));
  }
}

function reportNextStatuses(string $currentStatus): array
{
  switch ($currentStatus) {
    case 'pending':
      return ['in_progress', 'rejected'];
    case 'in_progress':
      return ['collected', 'rejected'];
    default:
      return [];
  }
}

function loadCitizenReport(PDO $pdo, int $reportId, int $citizenId): ?array
{
  $stmt = $pdo->prepare("
    SELECT wr.id, wc.name AS category, wr.description, wr.location_text, wr.photo_path,
           wr.status, wr.created_at, wr.updated_at
    FROM waste_reports wr
    JOIN waste_categories wc ON wc.id = wr.category_id
    WHERE wr.id = ? AND wr.citizen_id = ?
    LIMIT 1
  ");
  $stmt->execute([$reportId, $citizenId]);

  $report = $stmt->fetch(PDO::FETCH_ASSOC);
  return $report ?: null;
}

function loadCollectorReports(PDO $pdo, int $collectorId): array
{
  $stmt = $pdo->prepare("
    SELECT wr.id, wc.name AS category, wr.description, wr.location_text, wr.status,
           wr.created_at, wr.updated_at
    FROM waste_reports wr
    JOIN waste_categories wc ON wc.id = wr.category_id
    WHERE wr.assigned_to = ?
    ORDER BY wr.created_at DESC
  ");
  $stmt->execute([$collectorId]);

  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateCollectorReportStatus(PDO $pdo, int $reportId, int $collectorId, string $newStatus, string $notes = ''): array
{
  $result = [
    'success' => false,
    'message' => '',
    'updated_rows' => 0,
  ];

  if ($reportId <= 0) {
    $result['message'] = 'Report not found or not assigned to you.';
    return $result;
  }

  $pdo->beginTransaction();

  try {
    $lookup = $pdo->prepare("
      SELECT id, status
      FROM waste_reports
      WHERE id = ? AND assigned_to = ?
      LIMIT 1
    ");
    $lookup->execute([$reportId, $collectorId]);
    $report = $lookup->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
      $pdo->rollBack();
      $result['message'] = 'Report not found or not assigned to you.';
      return $result;
    }

    $allowedStatuses = reportNextStatuses($report['status']);
    if (!in_array($newStatus, $allowedStatuses, true)) {
      $pdo->rollBack();
      $result['message'] = 'That status transition is not allowed.';
      return $result;
    }

    $stmt = $pdo->prepare("
      UPDATE waste_reports
      SET status = ?, updated_at = CURRENT_TIMESTAMP
      WHERE id = ? AND assigned_to = ?
    ");
    $stmt->execute([$newStatus, $reportId, $collectorId]);
    $result['updated_rows'] = $stmt->rowCount();

    if ($result['updated_rows'] !== 1) {
      $pdo->rollBack();
      $result['message'] = 'Report not found or not assigned to you.';
      return $result;
    }

    $logNotes = $notes !== '' ? $notes : 'Changed status to ' . $newStatus;
    $log = $pdo->prepare("
      INSERT INTO collection_logs (report_id, collector_id, action, notes)
      VALUES (?, ?, ?, ?)
    ");
    $log->execute([$reportId, $collectorId, 'status_changed', $logNotes]);

    $pdo->commit();
    $result['success'] = true;

    return $result;
  } catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }

    throw $exception;
  }
}
