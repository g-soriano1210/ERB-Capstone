<?php
// my-submissions.php — Researcher view
require_once __DIR__ . '/includes/config.php';
requireLogin();

$user = currentUser();
if ($user['role'] !== 'researcher') {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$pdo  = getPDO();
$stmt = $pdo->prepare('SELECT * FROM submissions WHERE user_id = ? ORDER BY submitted_at DESC');
$stmt->execute([$user['id']]);
$submissions = $stmt->fetchAll();

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

$pageTitle  = 'My Submissions';
$activePage = 'my-submissions';
include __DIR__ . '/includes/header.php';
?>

<main class="page-main">

<div class="page-header">
  <div class="container">
    <h1>📂 My Submissions</h1>
    <p>Track all your ERB protocol submissions and their current status.</p>
  </div>
</div>

<section class="section-sm">
<div class="container">

  <div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
    <a href="<?= APP_URL ?>/submit.php" class="btn btn-green">+ New Submission</a>
  </div>

  <?php if (empty($submissions)): ?>
    <div class="card">
      <div class="card-body" style="text-align:center;padding:60px 40px;">
        <div style="font-size:48px;margin-bottom:16px;">📭</div>
        <h3 style="font-size:18px;margin-bottom:8px;">No submissions yet</h3>
        <p style="color:var(--neutral-500);margin-bottom:24px;">Start by submitting your first ERB protocol.</p>
        <a href="<?= APP_URL ?>/submit.php" class="btn btn-green">Submit a Protocol</a>
      </div>
    </div>
  <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:16px;">
      <?php foreach ($submissions as $sub):
        [$badgeClass, $badgeLabel] = $statusBadge[$sub['status']] ?? ['badge-pending','Pending'];
      ?>
      <div class="card">
        <div class="card-body">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div style="flex:1;min-width:0;">
              <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px;">
                <strong style="font-family:monospace;font-size:14px;color:var(--green-700);"><?= sanitize($sub['tracking_number']) ?></strong>
                <span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
              </div>
              <div style="font-size:13px;color:var(--neutral-600);margin-bottom:4px;">
                <strong><?= $typeLabels[$sub['submission_type']] ?? $sub['submission_type'] ?></strong>
                · Submitted <?= date('F d, Y', strtotime($sub['submitted_at'])) ?>
              </div>
              <div style="font-size:13px;color:var(--neutral-500);">
                <?= sanitize($sub['proponent_name']) ?> · <?= sanitize($sub['college']) ?>
              </div>
              <?php if ($sub['reviewer_notes']): ?>
              <div style="margin-top:10px;padding:10px 14px;background:var(--neutral-50);border-left:3px solid var(--green-400);border-radius:4px;font-size:13px;color:var(--neutral-700);line-height:1.6;">
                <strong style="font-size:12px;color:var(--neutral-500);display:block;margin-bottom:2px;">Reviewer Notes:</strong>
                <?= sanitize(substr($sub['reviewer_notes'], 0, 200)) . (strlen($sub['reviewer_notes']) > 200 ? '…' : '') ?>
              </div>
              <?php endif; ?>
            </div>
            <?php if ($sub['status'] === 'approved'): ?>
            <div style="flex-shrink:0;">
              <a href="<?= APP_URL ?>/certificate.php?id=<?= $sub['id'] ?>" target="_blank"
                 class="btn btn-green"
                 style="display:flex;align-items:center;gap:6px;">
                🎓 Download Certificate
              </a>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>
</section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
