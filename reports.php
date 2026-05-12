<?php
// reports.php — Analytics & Reports (Admin / Reviewer)
require_once __DIR__ . '/includes/config.php';
requireLogin();

$user = currentUser();
if ($user['role'] === 'researcher') {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$pdo = getPDO();

// ── Overall Status Counts ──────────────────────────────────────────────────────
$statusCounts = [];
foreach (['pending','under_review','approved','rejected','revision_needed'] as $s) {
    $st = $pdo->prepare('SELECT COUNT(*) FROM submissions WHERE status = ?');
    $st->execute([$s]);
    $statusCounts[$s] = (int)$st->fetchColumn();
}
$totalSubmissions = array_sum($statusCounts);

// ── Submission Type Counts ─────────────────────────────────────────────────────
$typeCounts = [];
foreach (['initial','review_student','review_funding','resubmission'] as $t) {
    $st = $pdo->prepare('SELECT COUNT(*) FROM submissions WHERE submission_type = ?');
    $st->execute([$t]);
    $typeCounts[$t] = (int)$st->fetchColumn();
}

// ── Monthly Trend (last 6 months) ─────────────────────────────────────────────
$monthly = $pdo->query("
    SELECT DATE_FORMAT(submitted_at, '%b %Y') AS month,
           DATE_FORMAT(submitted_at, '%Y-%m') AS sort_key,
           COUNT(*) AS total
    FROM submissions
    WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month, sort_key
    ORDER BY sort_key ASC
")->fetchAll();

// ── Per-Reviewer Stats (admin only) ───────────────────────────────────────────
$reviewerStats = [];
if ($user['role'] === 'admin') {
    $reviewerStats = $pdo->query("
        SELECT u.full_name, u.email,
               COUNT(s.id) AS assigned,
               SUM(s.status = 'approved') AS approved,
               SUM(s.status = 'rejected') AS rejected,
               SUM(s.status = 'revision_needed') AS revision,
               SUM(s.status IN ('pending','under_review')) AS in_progress
        FROM users u
        LEFT JOIN submissions s ON s.assigned_reviewer_id = u.id
        WHERE u.role IN ('reviewer','admin')
        GROUP BY u.id
        ORDER BY assigned DESC
    ")->fetchAll();
}

// ── Recent Approvals ──────────────────────────────────────────────────────────
$recentApproved = $pdo->query("
    SELECT s.tracking_number, s.proponent_name, s.college, s.submission_type, s.updated_at
    FROM submissions s
    WHERE s.status = 'approved'
    ORDER BY s.updated_at DESC
    LIMIT 10
")->fetchAll();

// ── College Breakdown ─────────────────────────────────────────────────────────
$collegeBreakdown = $pdo->query("
    SELECT college, COUNT(*) AS total
    FROM submissions
    GROUP BY college
    ORDER BY total DESC
    LIMIT 10
")->fetchAll();

$typeLabels = [
    'initial'        => 'Initial',
    'review_student' => 'For Review (Student)',
    'review_funding' => 'For Review (Funding)',
    'resubmission'   => 'Resubmission',
];
$statusLabels = [
    'pending'         => 'Pending',
    'under_review'    => 'Under Review',
    'approved'        => 'Approved',
    'rejected'        => 'Rejected',
    'revision_needed' => 'Revision Needed',
];

$pageTitle  = 'Reports & Analytics';
$activePage = 'reports';
include __DIR__ . '/includes/header.php';
?>

<main class="page-main">

<div class="page-header">
  <div class="container">
    <h1>📊 Reports & Analytics</h1>
    <p>Overview of ERB submission activity and review performance.</p>
  </div>
</div>

<section class="section-sm">
<div class="container">

  <!-- Summary Stats -->
  <div class="stats-grid" style="margin-bottom:32px;">
    <div class="stat-card">
      <div class="stat-value"><?= $totalSubmissions ?></div>
      <div class="stat-label">Total Submissions</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" style="color:#065f46;"><?= $statusCounts['approved'] ?></div>
      <div class="stat-label">Approved</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" style="color:#92400e;"><?= $statusCounts['pending'] ?></div>
      <div class="stat-label">Pending</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" style="color:#1e40af;"><?= $statusCounts['under_review'] ?></div>
      <div class="stat-label">Under Review</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" style="color:#991b1b;"><?= $statusCounts['rejected'] ?></div>
      <div class="stat-label">Rejected</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">

    <!-- Status Distribution -->
    <div class="card">
      <div class="card-body">
        <h3 style="font-size:15px;font-weight:700;color:var(--green-800);margin-bottom:20px;">📋 Status Distribution</h3>
        <?php if ($totalSubmissions > 0): ?>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <?php
          $statusColors = [
            'pending'         => '#f59e0b',
            'under_review'    => '#3b82f6',
            'approved'        => '#10b981',
            'rejected'        => '#ef4444',
            'revision_needed' => '#8b5cf6',
          ];
          foreach ($statusCounts as $s => $count):
            $pct = $totalSubmissions > 0 ? round($count / $totalSubmissions * 100) : 0;
          ?>
          <div>
            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
              <span style="font-weight:500;"><?= $statusLabels[$s] ?></span>
              <span style="color:var(--neutral-500);"><?= $count ?> (<?= $pct ?>%)</span>
            </div>
            <div style="height:8px;background:var(--neutral-100);border-radius:99px;overflow:hidden;">
              <div style="height:100%;width:<?= $pct ?>%;background:<?= $statusColors[$s] ?>;border-radius:99px;transition:width .4s;"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
          <p style="color:var(--neutral-500);font-size:14px;">No submissions yet.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Submission Type Breakdown -->
    <div class="card">
      <div class="card-body">
        <h3 style="font-size:15px;font-weight:700;color:var(--green-800);margin-bottom:20px;">📁 Submissions by Type</h3>
        <?php $typeTotal = array_sum($typeCounts); ?>
        <?php if ($typeTotal > 0): ?>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <?php
          $typeColors = ['#2d7a4f','#3b82f6','#8b5cf6','#f59e0b'];
          $ti = 0;
          foreach ($typeCounts as $t => $count):
            $pct = $typeTotal > 0 ? round($count / $typeTotal * 100) : 0;
          ?>
          <div>
            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
              <span style="font-weight:500;"><?= $typeLabels[$t] ?></span>
              <span style="color:var(--neutral-500);"><?= $count ?> (<?= $pct ?>%)</span>
            </div>
            <div style="height:8px;background:var(--neutral-100);border-radius:99px;overflow:hidden;">
              <div style="height:100%;width:<?= $pct ?>%;background:<?= $typeColors[$ti % 4] ?>;border-radius:99px;"></div>
            </div>
          </div>
          <?php $ti++; endforeach; ?>
        </div>
        <?php else: ?>
          <p style="color:var(--neutral-500);font-size:14px;">No submissions yet.</p>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- Monthly Trend -->
  <?php if (!empty($monthly)): ?>
  <div class="card" style="margin-bottom:24px;">
    <div class="card-body">
      <h3 style="font-size:15px;font-weight:700;color:var(--green-800);margin-bottom:20px;">📈 Monthly Submission Trend (Last 6 Months)</h3>
      <?php $maxMonthly = max(array_column($monthly, 'total')); ?>
      <div style="display:flex;align-items:flex-end;gap:16px;height:140px;padding-bottom:4px;">
        <?php foreach ($monthly as $m):
          $barH = $maxMonthly > 0 ? round($m['total'] / $maxMonthly * 120) : 4;
        ?>
        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;flex:1;">
          <span style="font-size:12px;font-weight:600;color:var(--green-700);"><?= $m['total'] ?></span>
          <div style="width:100%;height:<?= max($barH,4) ?>px;background:var(--green-400);border-radius:4px 4px 0 0;transition:height .4s;"></div>
          <span style="font-size:11px;color:var(--neutral-500);text-align:center;"><?= sanitize($m['month']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">

    <!-- College Breakdown -->
    <?php if (!empty($collegeBreakdown)): ?>
    <div class="card">
      <div class="card-body">
        <h3 style="font-size:15px;font-weight:700;color:var(--green-800);margin-bottom:16px;">🏛 Submissions by College</h3>
        <div class="table-wrap" style="margin:0;">
          <table>
            <thead>
              <tr><th>College / Unit</th><th style="text-align:right;">Count</th></tr>
            </thead>
            <tbody>
              <?php foreach ($collegeBreakdown as $row): ?>
              <tr>
                <td style="font-size:13px;"><?= sanitize($row['college'] ?: '—') ?></td>
                <td style="text-align:right;font-weight:600;color:var(--green-700);"><?= $row['total'] ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Recent Approvals -->
    <?php if (!empty($recentApproved)): ?>
    <div class="card">
      <div class="card-body">
        <h3 style="font-size:15px;font-weight:700;color:var(--green-800);margin-bottom:16px;">✅ Recent Approvals</h3>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <?php foreach ($recentApproved as $a): ?>
          <div style="padding:10px 14px;background:var(--neutral-50);border-radius:6px;border-left:3px solid #10b981;">
            <div style="font-size:12px;font-weight:700;color:var(--green-700);"><?= sanitize($a['tracking_number']) ?></div>
            <div style="font-size:13px;color:var(--neutral-700);"><?= sanitize($a['proponent_name']) ?></div>
            <div style="font-size:11px;color:var(--neutral-400);margin-top:2px;">
              <?= $typeLabels[$a['submission_type']] ?? '' ?> · <?= date('M d, Y', strtotime($a['updated_at'])) ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Reviewer Performance (Admin only) -->
  <?php if ($user['role'] === 'admin' && !empty($reviewerStats)): ?>
  <div class="card">
    <div class="card-body" style="padding-bottom:0;">
      <h3 style="font-size:15px;font-weight:700;color:var(--green-800);margin-bottom:16px;">👥 Reviewer Performance</h3>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Reviewer</th>
            <th>Email</th>
            <th style="text-align:center;">Assigned</th>
            <th style="text-align:center;">Approved</th>
            <th style="text-align:center;">Rejected</th>
            <th style="text-align:center;">Revision</th>
            <th style="text-align:center;">In Progress</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reviewerStats as $rv): ?>
          <tr>
            <td style="font-size:13px;font-weight:500;"><?= sanitize($rv['full_name']) ?></td>
            <td style="font-size:12px;color:var(--neutral-500);"><?= sanitize($rv['email']) ?></td>
            <td style="text-align:center;font-weight:700;color:var(--green-700);"><?= $rv['assigned'] ?></td>
            <td style="text-align:center;color:#065f46;"><?= $rv['approved'] ?></td>
            <td style="text-align:center;color:#991b1b;"><?= $rv['rejected'] ?></td>
            <td style="text-align:center;color:#5b21b6;"><?= $rv['revision'] ?></td>
            <td style="text-align:center;color:#1e40af;"><?= $rv['in_progress'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>
</section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
