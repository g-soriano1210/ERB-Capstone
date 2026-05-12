<?php
// success.php
require_once __DIR__ . '/includes/config.php';
requireLogin();
startSession();

$tracking = $_SESSION['last_tracking'] ?? null;
$submissionId = $_SESSION['last_submission_id'] ?? null;

if (!$tracking) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

// Clear from session
unset($_SESSION['last_tracking'], $_SESSION['last_submission_id']);

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM submissions WHERE tracking_number = ?');
$stmt->execute([$tracking]);
$sub = $stmt->fetch();

$typeLabels = [
    'initial'        => 'Initial Submission',
    'review_student' => 'For Review (Student)',
    'review_funding' => 'For Review (Funding)',
    'resubmission'   => 'Resubmission',
];

$user = currentUser();
$pageTitle = 'Submission Successful';
include __DIR__ . '/includes/header.php';
?>

<main class="page-main">
<div class="success-page">
  <div class="success-card">
    <div class="success-icon">✓</div>

    <h2>Submission Received!</h2>
    <p>
      Your ERB protocol has been successfully submitted. A confirmation email has been sent to
      <strong><?= sanitize($user['email']) ?></strong> with your submission details.
    </p>

    <div class="tracking-box">
      <label>Your Tracking Number</label>
      <span><?= sanitize($tracking) ?></span>
    </div>

    <dl class="tracking-details" style="margin-bottom:28px;">
      <dt>Date Submitted</dt>
      <dd><?= $sub ? date('F d, Y \a\t h:i A', strtotime($sub['submitted_at'])) : date('F d, Y \a\t h:i A') ?></dd>

      <dt>Proponent / Project Leader</dt>
      <dd><?= sanitize($sub['proponent_name'] ?? '') ?></dd>

      <dt>College / Campus / Unit</dt>
      <dd><?= sanitize($sub['college'] ?? '') ?></dd>

      <dt>Designation</dt>
      <dd><?= sanitize($sub['designation'] ?? '') ?></dd>

      <dt>Submission Type</dt>
      <dd><?= $typeLabels[$sub['submission_type'] ?? ''] ?? '' ?></dd>

      <dt>Status</dt>
      <dd><span class="badge badge-pending">Pending Review</span></dd>
    </dl>

    <div class="alert alert-info" style="text-align:left;">
      <div>
        📬 <strong>What happens next?</strong><br>
        The ERB Secretariat will review your submission and notify you via email. 
        Please keep your tracking number for follow-up purposes.
        You may contact them at <strong>erb@cvsu.edu.ph</strong>.
      </div>
    </div>

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:8px;">
      <a href="<?= APP_URL ?>/my-submissions.php" class="btn btn-green">My Submissions</a>
      <a href="<?= APP_URL ?>/submit.php" class="btn btn-outline" style="color:var(--neutral-700);border-color:var(--neutral-300);">
        Submit Another
      </a>
    </div>
  </div>
</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
