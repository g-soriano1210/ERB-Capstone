<?php
// submission-detail.php — Full submission view for Reviewer / Admin
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/mailer.php';
requireLogin();

$user = currentUser();
if ($user['role'] === 'researcher') {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/dashboard.php'); exit; }

$pdo = getPDO();

// Fetch submission + submitter info
$stmt = $pdo->prepare('
    SELECT s.*, u.email, u.full_name AS submitter_name, u.college AS submitter_college,
           r.full_name AS reviewer_name
    FROM submissions s
    JOIN users u ON u.id = s.user_id
    LEFT JOIN users r ON r.id = s.assigned_reviewer_id
    WHERE s.id = ?
');
$stmt->execute([$id]);
$sub = $stmt->fetch();
if (!$sub) { header('Location: ' . APP_URL . '/dashboard.php'); exit; }

// Fetch uploaded files
$files = $pdo->prepare('SELECT * FROM submission_files WHERE submission_id = ? ORDER BY id');
$files->execute([$id]);
$uploadedFiles = $files->fetchAll();

// Fetch all reviewers for assignment dropdown (admin only)
$reviewers = [];
if ($user['role'] === 'admin') {
    $revStmt = $pdo->query("SELECT id, full_name, email FROM users WHERE role IN ('reviewer','admin') ORDER BY full_name");
    $reviewers = $revStmt->fetchAll();
}

$msg   = '';
$error = '';

// Handle reviewer assignment (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_reviewer']) && $user['role'] === 'admin') {
    $reviewerId = (int)$_POST['reviewer_id'] ?: null;
    $pdo->prepare('UPDATE submissions SET assigned_reviewer_id = ? WHERE id = ?')
        ->execute([$reviewerId, $id]);
    $msg = $reviewerId ? 'Reviewer assigned successfully.' : 'Reviewer assignment cleared.';
    // Reload
    $stmt->execute([$id]);
    $sub = $stmt->fetch();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'] ?? '';
    $notes     = trim($_POST['reviewer_notes'] ?? '');
    $valid     = ['pending','under_review','approved','rejected','revision_needed'];

    if (!in_array($newStatus, $valid)) {
        $error = 'Invalid status selected.';
    } else {
        $pdo->prepare('UPDATE submissions SET status = ?, reviewer_notes = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$newStatus, $notes, $id]);

        // Notify researcher
        $submitterStmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $submitterStmt->execute([$sub['user_id']]);
        $submitter = $submitterStmt->fetch();

        $statusLabels = [
            'pending'         => 'Pending',
            'under_review'    => 'Under Review',
            'approved'        => 'Approved ✅',
            'rejected'        => 'Rejected ❌',
            'revision_needed' => 'Revision Needed 🔄',
        ];
        $statusLabel = $statusLabels[$newStatus] ?? $newStatus;
        $subject     = "ERB Submission Update — {$sub['tracking_number']} is now: {$statusLabel}";
        $notesHtml   = $notes ? "<p><strong>Reviewer Notes:</strong><br>" . nl2br(htmlspecialchars($notes)) . "</p>" : '';

        $emailBody = <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:30px;">
  <div style="max-width:600px;margin:auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
    <div style="background:linear-gradient(135deg,#1a4731 0%,#2d7a4f 100%);padding:32px 40px;text-align:center;">
      <h1 style="color:#f5c842;font-size:20px;margin:0 0 6px;">Cavite State University</h1>
      <p style="color:#a8d5b5;margin:0;font-size:13px;">Ethics Review Board — Submission Update</p>
    </div>
    <div style="padding:36px;">
      <p style="color:#444;">Dear <strong>{$submitter['full_name']}</strong>,</p>
      <p style="color:#444;">Your ERB submission status has been updated.</p>
      <table style="width:100%;border-collapse:collapse;margin:20px 0;font-size:14px;">
        <tr style="background:#f0f7f3;">
          <td style="padding:10px 14px;font-weight:600;width:40%;">Tracking Number</td>
          <td style="padding:10px 14px;color:#1a4731;font-weight:700;">{$sub['tracking_number']}</td>
        </tr>
        <tr>
          <td style="padding:10px 14px;font-weight:600;">New Status</td>
          <td style="padding:10px 14px;"><strong>{$statusLabel}</strong></td>
        </tr>
      </table>
      {$notesHtml}
      <p style="color:#666;font-size:13px;">For questions, contact the ERB Secretariat at <a href="mailto:erb@cvsu.edu.ph" style="color:#2d7a4f;">erb@cvsu.edu.ph</a>.</p>
    </div>
    <div style="background:#f0f0f0;padding:16px 40px;text-align:center;">
      <p style="margin:0;color:#888;font-size:12px;">© <?= date('Y') ?> Cavite State University — Ethics Review Board</p>
    </div>
  </div>
</body></html>
HTML;
        @sendEmail($submitter['email'], $submitter['full_name'], $subject, $emailBody);

        $stmt->execute([$id]);
        $sub = $stmt->fetch();
        $msg = 'Status updated and researcher notified.';
    }
}

$typeLabels = [
    'initial'        => 'Initial Submission',
    'review_student' => 'For Review (Student)',
    'review_funding' => 'For Review (Funding)',
    'resubmission'   => 'Resubmission',
];
$fieldLabels = [
    'informed_consent'       => 'Informed Consent Form',
    'research_instrument'    => 'Research Instrument',
    'short_description'      => 'Short Description Form',
    'cv_proponent'           => 'Curriculum Vitae of Proponent/s and Adviser',
    'research_proposal'      => 'Research Proposal',
    'certificate_validation' => 'Certificate of Validation of Instrument',
    'routing_slip'           => 'Routing Slip',
    'protocol_package'       => 'Protocol Package ERB Forms 1–4',
    'proof_of_review'        => 'Proof of Review',
    'resubmission_request'   => 'Resubmission Request',
    'resubmission_form'      => 'Resubmission Form',
];
$statusBadge = [
    'pending'         => ['badge-pending',     'Pending'],
    'under_review'    => ['badge-under-review', 'Under Review'],
    'approved'        => ['badge-approved',     'Approved'],
    'rejected'        => ['badge-rejected',     'Rejected'],
    'revision_needed' => ['badge-revision',     'Revision Needed'],
];
[$badgeClass, $badgeLabel] = $statusBadge[$sub['status']] ?? ['badge-pending','Pending'];

$pageTitle  = 'Submission — ' . $sub['tracking_number'];
$activePage = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

<main class="page-main">

<div class="page-header">
  <div class="container-md">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px;">
      <a href="<?= APP_URL ?>/dashboard.php" style="color:rgba(255,255,255,.6);text-decoration:none;font-size:13px;">
        ← Back to Dashboard
      </a>
    </div>
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <h1 style="font-size:clamp(20px,3vw,28px);"><?= sanitize($sub['tracking_number']) ?></h1>
        <p><?= $typeLabels[$sub['submission_type']] ?? '' ?> · Submitted <?= date('F d, Y', strtotime($sub['submitted_at'])) ?></p>
      </div>
      <span class="badge <?= $badgeClass ?>" style="font-size:13px;padding:6px 16px;margin-top:4px;"><?= $badgeLabel ?></span>
    </div>
  </div>
</div>

<section class="section-sm">
<div class="container-md">

  <?php if ($msg):  ?><div class="alert alert-success">✅ <?= sanitize($msg) ?></div><?php endif; ?>
  <?php if ($error):?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">

    <!-- LEFT: Submission Info + Files -->
    <div style="display:flex;flex-direction:column;gap:20px;">

      <!-- Proponent Info -->
      <div class="card">
        <div class="card-body">
          <h3 style="font-size:15px;font-weight:700;color:var(--green-800);margin-bottom:18px;">👤 Proponent Information</h3>
          <dl style="display:grid;grid-template-columns:160px 1fr;gap:10px 16px;font-size:14px;">
            <dt style="font-weight:600;color:var(--neutral-500);">Proponent Name</dt>
            <dd><?= sanitize($sub['proponent_name']) ?></dd>
            <dt style="font-weight:600;color:var(--neutral-500);">College / Unit</dt>
            <dd><?= sanitize($sub['college']) ?></dd>
            <dt style="font-weight:600;color:var(--neutral-500);">Designation</dt>
            <dd><?= sanitize($sub['designation']) ?></dd>
            <dt style="font-weight:600;color:var(--neutral-500);">Submission Type</dt>
            <dd><?= $typeLabels[$sub['submission_type']] ?? $sub['submission_type'] ?></dd>
            <dt style="font-weight:600;color:var(--neutral-500);">Account Email</dt>
            <dd><?= sanitize($sub['email']) ?></dd>
            <dt style="font-weight:600;color:var(--neutral-500);">Submitted At</dt>
            <dd><?= date('F d, Y \a\t h:i A', strtotime($sub['submitted_at'])) ?></dd>
            <dt style="font-weight:600;color:var(--neutral-500);">Last Updated</dt>
            <dd><?= date('F d, Y \a\t h:i A', strtotime($sub['updated_at'])) ?></dd>
            <dt style="font-weight:600;color:var(--neutral-500);">Assigned Reviewer</dt>
            <dd><?= $sub['reviewer_name'] ? sanitize($sub['reviewer_name']) : '<span style="color:var(--neutral-400);">Not assigned</span>' ?></dd>
          </dl>
        </div>
      </div>

      <!-- Uploaded Files -->
      <div class="card">
        <div class="card-body">
          <h3 style="font-size:15px;font-weight:700;color:var(--green-800);margin-bottom:18px;">📎 Submitted Documents</h3>
          <?php if (empty($uploadedFiles)): ?>
            <div class="alert alert-warning">No files found for this submission.</div>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:10px;">
              <?php foreach ($uploadedFiles as $file):
                $label       = $fieldLabels[$file['file_field']] ?? $file['file_field'];
                $sizeKb      = round($file['file_size'] / 1024, 1);
                $ext         = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
                $icons       = ['pdf'=>'📄','doc'=>'📝','docx'=>'📝','jpg'=>'🖼️','jpeg'=>'🖼️','png'=>'🖼️'];
                $icon        = $icons[$ext] ?? '📎';
                $downloadUrl = APP_URL . '/download.php?file=' . urlencode($file['stored_name']) . '&sub=' . $file['submission_id'];
              ?>
              <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:var(--neutral-50);border:1px solid var(--neutral-200);border-radius:var(--radius-md);gap:12px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                  <span style="font-size:24px;flex-shrink:0;"><?= $icon ?></span>
                  <div style="min-width:0;">
                    <div style="font-size:13px;font-weight:600;color:var(--neutral-800);margin-bottom:2px;"><?= sanitize($label) ?></div>
                    <div style="font-size:12px;color:var(--neutral-500);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                      <?= sanitize($file['original_name']) ?> · <?= $sizeKb ?> KB
                    </div>
                  </div>
                </div>
                <a href="<?= $downloadUrl ?>" class="btn btn-sm btn-green" style="flex-shrink:0;" target="_blank">⬇ Download</a>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Reviewer Notes (read-only) -->
      <?php if ($sub['reviewer_notes']): ?>
      <div class="card">
        <div class="card-body">
          <h3 style="font-size:15px;font-weight:700;color:var(--green-800);margin-bottom:12px;">💬 Current Reviewer Notes</h3>
          <div style="background:var(--neutral-50);border-left:4px solid var(--green-400);padding:14px 16px;border-radius:6px;font-size:14px;line-height:1.7;color:var(--neutral-700);">
            <?= nl2br(sanitize($sub['reviewer_notes'])) ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <!-- RIGHT: Action Panel -->
    <div style="display:flex;flex-direction:column;gap:16px;position:sticky;top:88px;">

      <?php if ($user['role'] === 'admin'): ?>
      <!-- Reviewer Assignment (Admin only) -->
      <div class="card">
        <div class="card-body">
          <h3 style="font-size:15px;font-weight:700;color:var(--green-800);margin-bottom:14px;">
            👥 Assign Reviewer
          </h3>
          <form method="POST" action="">
            <div class="form-group" style="margin-bottom:12px;">
              <label class="form-label">Assigned Reviewer</label>
              <select name="reviewer_id" class="form-control">
                <option value="">— Unassigned —</option>
                <?php foreach ($reviewers as $rev): ?>
                  <option value="<?= $rev['id'] ?>" <?= $sub['assigned_reviewer_id'] == $rev['id'] ? 'selected' : '' ?>>
                    <?= sanitize($rev['full_name']) ?> (<?= sanitize($rev['email']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <p class="form-hint">Reviewers will see this submission in their dashboard.</p>
            </div>
            <button type="submit" name="assign_reviewer" class="btn btn-green btn-full">
              💾 Save Assignment
            </button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <!-- Review Action Panel -->
      <div class="card">
        <div class="card-body">
          <h3 style="font-size:15px;font-weight:700;color:var(--green-800);margin-bottom:18px;">✏️ Review Action</h3>
          <form method="POST" action="">
            <div class="form-group">
              <label class="form-label">Update Status</label>
              <select name="status" class="form-control">
                <option value="pending"         <?= $sub['status']==='pending'         ?'selected':'' ?>>⏳ Pending</option>
                <option value="under_review"    <?= $sub['status']==='under_review'    ?'selected':'' ?>>🔍 Under Review</option>
                <option value="approved"        <?= $sub['status']==='approved'        ?'selected':'' ?>>✅ Approved</option>
                <option value="rejected"        <?= $sub['status']==='rejected'        ?'selected':'' ?>>❌ Rejected</option>
                <option value="revision_needed" <?= $sub['status']==='revision_needed' ?'selected':'' ?>>🔄 Revision Needed</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Reviewer Notes</label>
              <textarea name="reviewer_notes" class="form-control" rows="6"
                        placeholder="Write feedback, revision requests, or approval details. This will be emailed to the researcher."
                        style="resize:vertical;"><?= sanitize($sub['reviewer_notes'] ?? '') ?></textarea>
              <p class="form-hint">Researcher will be notified via email.</p>
            </div>
            <button type="submit" name="update_status" class="btn btn-green btn-full">💾 Save Review</button>
          </form>

          <?php if ($sub['status'] === 'approved'): ?>
          <hr style="margin:16px 0;border-color:var(--neutral-100);">
          <a href="<?= APP_URL ?>/certificate.php?id=<?= $id ?>" target="_blank"
             class="btn btn-full"
             style="background:var(--green-50);color:var(--green-800);border:1px solid var(--green-300);text-align:center;display:block;padding:10px;">
            🎓 View Approval Certificate
          </a>
          <?php endif; ?>

          <hr style="margin:16px 0;border-color:var(--neutral-100);">
          <div style="font-size:12px;color:var(--neutral-500);">
            <strong>Tracking #</strong><br>
            <span style="font-family:monospace;font-size:13px;color:var(--green-700);"><?= sanitize($sub['tracking_number']) ?></span>
          </div>
        </div>
      </div>

    </div>
  </div>

</div>
</section>
</main>

<style>
@media (max-width: 768px) {
  .detail-grid { grid-template-columns: 1fr !important; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
