<?php
require_once __DIR__ . '/../auth_check.php';
require_role('admin');
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $reportId = (int)($_POST['report_id'] ?? 0);
  $collectorId = (int)($_POST['collector_id'] ?? 0);

  if ($reportId > 0 && $collectorId > 0) {
    $stmt = $pdo->prepare("UPDATE waste_reports SET assigned_to = ?, status = 'in_progress' WHERE id = ?");
    $stmt->execute([$collectorId, $reportId]);
  }
  header("Location: index.php");
  exit();
}

/* ----------------------------
   Dashboard stats (summary strip)
   ---------------------------- */
$stats = [
  'reports'     => 0,
  'pending'     => 0,
  'in_progress' => 0,
  'collected'   => 0,
  'rejected'    => 0,
  'citizens'    => 0,
  'collectors'  => 0,
];

try {
  $stats['reports']     = (int)$pdo->query("SELECT COUNT(*) FROM waste_reports")->fetchColumn();
  $stats['pending']     = (int)$pdo->query("SELECT COUNT(*) FROM waste_reports WHERE status = 'pending'")->fetchColumn();
  $stats['in_progress'] = (int)$pdo->query("SELECT COUNT(*) FROM waste_reports WHERE status = 'in_progress'")->fetchColumn();
  $stats['collected']   = (int)$pdo->query("SELECT COUNT(*) FROM waste_reports WHERE status = 'collected'")->fetchColumn();
  $stats['rejected']    = (int)$pdo->query("SELECT COUNT(*) FROM waste_reports WHERE status = 'rejected'")->fetchColumn();
  $stats['collectors']  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'collector' AND is_active = 1")->fetchColumn();
  $stats['citizens']    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'citizen' AND is_active = 1")->fetchColumn();
} catch (PDOException $e) {
  // keep zeros; page still loads
}

/* ----------------------------
   Waste-by-category data
   ---------------------------- */
$categoryCounts = [];
try {
  $stmt = $pdo->query("
    SELECT wc.name AS category, COUNT(wr.id) AS cnt
    FROM waste_categories wc
    LEFT JOIN waste_reports wr ON wr.category_id = wc.id
    GROUP BY wc.id, wc.name
    ORDER BY cnt DESC, wc.name ASC
  ");
  $categoryCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $categoryCounts = [];
}

$maxCategory = 0;
foreach ($categoryCounts as $row) {
  $maxCategory = max($maxCategory, (int)$row['cnt']);
}

/* ----------------------------
   Existing report list + assign
   ---------------------------- */
$collectors = $pdo->query("SELECT id, name, email FROM users WHERE role='collector'")->fetchAll(PDO::FETCH_ASSOC);

$reports = $pdo->query("
  SELECT wr.*, u.name AS citizen_name, wc.name AS category_name, c.name AS collector_name
  FROM waste_reports wr
  JOIN users u ON u.id = wr.citizen_id
  JOIN waste_categories wc ON wc.id = wr.category_id
  LEFT JOIN users c ON c.id = wr.assigned_to
  ORDER BY wr.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    /* small self-contained chart styles (no external libs) */
    .stat-grid {
      display: grid;
      grid-template-columns: repeat(7, minmax(120px, 1fr));
      gap: 10px;
    }
    @media (max-width: 980px) {
      .stat-grid { grid-template-columns: repeat(2, 1fr); }
    }
    .stat {
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 12px;
      background: #fff;
    }
    .stat .k { color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: .4px; }
    .stat .v { font-size: 22px; font-weight: 800; margin-top: 4px; }
    .bars { display: grid; gap: 10px; margin-top: 10px; }
    .bar-row { display: grid; grid-template-columns: 160px 1fr 60px; gap: 10px; align-items: center; }
    @media (max-width: 700px) { .bar-row { grid-template-columns: 1fr; } }
    .bar {
      height: 12px;
      background: #e9eef2;
      border: 1px solid var(--border);
      border-radius: 999px;
      overflow: hidden;
    }
    .bar > span {
      display: block;
      height: 100%;
      width: 0%;
      background: var(--accent);
    }
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
  </style>
</head>
<body data-base="..">
  <div class="app-bar">
    <h1>Admin Dashboard</h1>
    <span class="user">
      Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
      &nbsp;|&nbsp; <a href="users.php">Users</a>
      &nbsp;|&nbsp; <a href="export_reports.php">Export CSV</a>
      &nbsp;|&nbsp; <a href="../auth/logout.php">Logout</a>
    </span>
  </div>

  <div class="container">

    <!-- NEW: stats overview strip -->
    <div class="card">
      <h3 style="margin-top:0;">Overview</h3>
      <div class="stat-grid">
        <div class="stat"><div class="k">Total Reports</div><div class="v"><?php echo (int)$stats['reports']; ?></div></div>
        <div class="stat"><div class="k">Pending</div><div class="v"><?php echo (int)$stats['pending']; ?></div></div>
        <div class="stat"><div class="k">In Progress</div><div class="v"><?php echo (int)$stats['in_progress']; ?></div></div>
        <div class="stat"><div class="k">Collected</div><div class="v"><?php echo (int)$stats['collected']; ?></div></div>
        <div class="stat"><div class="k">Rejected</div><div class="v"><?php echo (int)$stats['rejected']; ?></div></div>
        <div class="stat"><div class="k">Active Citizens</div><div class="v"><?php echo (int)$stats['citizens']; ?></div></div>
        <div class="stat"><div class="k">Active Collectors</div><div class="v"><?php echo (int)$stats['collectors']; ?></div></div>
      </div>
    </div>

    <!-- NEW: waste-by-category chart -->
    <div class="card">
      <div class="toolbar">
        <h3 style="margin:0;">Waste by Category</h3>
        <span class="grow"></span>
        <span class="muted">Counts grouped by category</span>
      </div>

      <?php if (!$categoryCounts): ?>
        <p class="empty">No category data available.</p>
      <?php else: ?>
        <div class="bars" id="category-bars" data-max="<?php echo (int)$maxCategory; ?>">
          <?php foreach ($categoryCounts as $row): ?>
            <?php
              $cat = (string)$row['category'];
              $cnt = (int)$row['cnt'];
            ?>
            <div class="bar-row" data-count="<?php echo $cnt; ?>">
              <div><strong><?php echo htmlspecialchars($cat); ?></strong></div>
              <div class="bar"><span></span></div>
              <div class="mono"><?php echo $cnt; ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Existing: reports + assignment -->
    <div class="card">
      <div class="toolbar">
        <h3 style="margin:0;">All Reports</h3>
        <span class="grow"></span>
        <?php if ($reports): ?>
          <input type="text" data-filter="reports-table" placeholder="Filter reports…" style="max-width:260px;">
        <?php endif; ?>
      </div>

      <?php if (!$reports): ?>
        <p class="empty">No reports submitted yet.</p>
      <?php else: ?>
      <table class="data" id="reports-table">
        <thead>
          <tr>
            <th>ID</th><th>Citizen</th><th>Category</th><th>Description</th><th>Location</th>
            <th>Photo</th><th>Status</th><th>Assigned To</th><th>Assign Collector</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($reports as $r): ?>
          <tr>
            <td><?php echo (int)$r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['citizen_name']); ?></td>
            <td><?php echo htmlspecialchars($r['category_name']); ?></td>
            <td><?php echo htmlspecialchars($r['description']); ?></td>
            <td><?php echo htmlspecialchars($r['location_text']); ?></td>
            <td>
              <?php if (!empty($r['photo_path'])): ?>
                <a class="btn secondary small" href="photo.php?report_id=<?php echo (int)$r['id']; ?>" target="_blank" rel="noopener">View</a>
              <?php else: ?>
                <span class="muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?php echo htmlspecialchars($r['status']); ?>">
                <?php echo htmlspecialchars(str_replace('_', ' ', $r['status'])); ?>
              </span>
            </td>
            <td><?php echo htmlspecialchars($r['collector_name'] ?? 'Not assigned'); ?></td>
            <td>
              <form method="POST" style="display:flex; gap:6px; margin:0;"
                    data-confirm="Assign report #<?php echo (int)$r['id']; ?> to the selected collector?">
                <input type="hidden" name="report_id" value="<?php echo (int)$r['id']; ?>">
                <select name="collector_id" required style="width:auto;">
                  <option value="">-- select --</option>
                  <?php foreach ($collectors as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>">
                      <?php echo htmlspecialchars($c['name'] . " (" . $c['email'] . ")"); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn small">Assign</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <script src="../assets/app.js"></script>
  <script>
    // Vanilla JS: set bar widths based on max
    (function () {
      var wrap = document.getElementById("category-bars");
      if (!wrap) return;
      var max = Number(wrap.getAttribute("data-max")) || 0;
      if (max <= 0) return;

      wrap.querySelectorAll(".bar-row").forEach(function (row) {
        var count = Number(row.getAttribute("data-count")) || 0;
        var pct = Math.round((count / max) * 100);
        var fill = row.querySelector(".bar > span");
        if (fill) fill.style.width = pct + "%";
      });
    })();
  </script>
</body>
</html>