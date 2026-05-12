<?php
// dashboard.php — Reviewer / Admin dashboard
require_once __DIR__ . '/includes/config.php';
requireLogin();

$user = currentUser();
if ($user['role'] === 'researcher') {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$pdo = getPDO();

// Stats
$stats = [];
foreach (['pending','under_review','approved','rejected','revision_needed'] as $s) {
    $st = $pdo->prepare('SELECT COUNT(*) FROM submissions WHERE status = ?');
    $st->execute([$s]);
    $stats[$s] = $st->fetchColumn();
}
$total = array_sum($stats);

// Recent submissions
$recent = $pdo->query('
    SELECT s.*, u.email, u.full_name
    FROM submissions s
    JOIN users u ON u.id = s.user_id
    ORDER BY s.submitted_at DESC
    LIMIT 20
')->fetchAll();

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $sid    = (int)($_POST['submission_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $notes  = trim($_POST['notes'] ?? '');
    $valid  = ['pending','under_review','approved','rejected','revision_needed'];
    if ($sid && in_array($status, $valid)) {
        $upd = $pdo->prepare('UPDATE submissions SET status = ?, reviewer_notes = ? WHERE id = ?');
        $upd->execute([$status, $notes, $sid]);
        header('Location: ' . APP_URL . '/dashboard.php?updated=1');
        exit;
    }
}

$typeLabels = [
    'initial'        => 'Initial',
    'review_student' => 'For Review (Student)',
    'review_funding' => 'For Review (Funding)',
    'resubmission'   => 'Resubmission',
];

$statusBadge = [
    'pending'         => ['badge-pending',      'Pending'],
    'under_review'    => ['badge-under-review',  'Under Review'],
    'approved'        => ['badge-approved',      'Approved'],
    'rejected'        => ['badge-rejected',      'Rejected'],
    'revision_needed' => ['badge-revision',      'Revision Needed'],
];

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

<main class="page-main">

<div class="page-header">
  <div class="container">
    <h1>📊 ERB Review Dashboard</h1>
    <p>Manage and review submitted research ethics protocols.</p>
  </div>
</div>

<section class="section-sm">
<div class="container">

  <?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success">✅ Submission status updated successfully.</div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats-grid" style="margin-bottom:32px;">
    <div class="stat-card">
      <div class="stat-value"><?= $total ?></div>
      <div class="stat-label">Total Submissions</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" style="color:#92400e;"><?= $stats['pending'] ?></div>
      <div class="stat-label">Pending</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" style="color:#1e40af;"><?= $stats['under_review'] ?></div>
      <div class="stat-label">Under Review</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" style="color:#065f46;"><?= $stats['approved'] ?></div>
      <div class="stat-label">Approved</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" style="color:#991b1b;"><?= $stats['rejected'] ?></div>
      <div class="stat-label">Rejected</div>
    </div>
  </div>

  <!-- Submissions Table -->
  <div class="card">
    <div class="card-body" style="padding-bottom:0;">
      <h3 style="font-size:16px;font-weight:600;margin-bottom:16px;">All Submissions</h3>
    </div>
    <div class="table-wrap">
      <?php if (empty($recent)): ?>
        <div style="padding:40px;text-align:center;color:var(--neutral-500);">No submissions yet.</div>
      <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Tracking #</th>
            <th>Proponent</th>
            <th>Email</th>
            <th>Type</th>
            <th>Submitted</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $sub):
            [$badgeClass, $badgeLabel] = $statusBadge[$sub['status']] ?? ['badge-pending','Pending'];
          ?>
          <tr>
            <td><strong style="color:var(--green-700);font-size:12px;"><?= sanitize($sub['tracking_number']) ?></strong></td>
            <td style="font-size:13px;"><?= sanitize($sub['proponent_name']) ?></td>
            <td style="font-size:12px;color:var(--neutral-500);"><?= sanitize($sub['email']) ?></td>
            <td style="font-size:12px;"><?= $typeLabels[$sub['submission_type']] ?? '' ?></td>
            <td style="font-size:12px;color:var(--neutral-500);"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
            <td><span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
            <td>
              <a href="<?= APP_URL ?>/submission-detail.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-green">
                View &amp; Review
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

</div>
</section>
</main>


<?php include __DIR__ . '/includes/footer.php'; ?>
