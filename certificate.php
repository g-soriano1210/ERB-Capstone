<?php
// certificate.php — Approval certificate for approved submissions
require_once __DIR__ . '/includes/config.php';
requireLogin();

$user = currentUser();
$id   = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/index.php'); exit; }

$pdo  = getPDO();
$stmt = $pdo->prepare('
    SELECT s.*, u.full_name AS submitter_name, u.email AS submitter_email, u.college AS submitter_college,
           r.full_name AS reviewer_name
    FROM submissions s
    JOIN users u ON u.id = s.user_id
    LEFT JOIN users r ON r.id = s.assigned_reviewer_id
    WHERE s.id = ?
');
$stmt->execute([$id]);
$sub = $stmt->fetch();

if (!$sub) { header('Location: ' . APP_URL . '/index.php'); exit; }

// Researchers can only view their own certificates; reviewers/admins can view any
if ($user['role'] === 'researcher' && $sub['user_id'] != $user['id']) {
    http_response_code(403); exit('Access denied.');
}

// Only approved submissions get a certificate
if ($sub['status'] !== 'approved') {
    header('Location: ' . APP_URL . '/my-submissions.php');
    exit;
}

$typeLabels = [
    'initial'        => 'Initial Submission',
    'review_student' => 'For Review (Student)',
    'review_funding' => 'For Review (Funding)',
    'resubmission'   => 'Resubmission',
];
$approvalDate = date('F d, Y', strtotime($sub['updated_at']));
$certNumber   = 'CERT-' . strtoupper(substr(md5($sub['tracking_number']), 0, 8));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Approval Certificate — <?= sanitize($sub['tracking_number']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { background: #f0f4f0; font-family: 'DM Sans', Arial, sans-serif; padding: 40px 20px; }
    .cert-wrap { max-width: 820px; margin: 0 auto; }
    .cert-actions { display: flex; gap: 12px; justify-content: flex-end; margin-bottom: 20px; }
    .cert-actions a { background: #2d7a4f; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600; }
    .cert-actions a.back { background: #6b7280; }
    .certificate {
      background: #fff;
      border: 2px solid #1a4731;
      border-radius: 4px;
      padding: 60px 64px;
      position: relative;
      box-shadow: 0 8px 40px rgba(0,0,0,.12);
    }
    .cert-border {
      position: absolute; inset: 12px;
      border: 1px solid #2d7a4f;
      border-radius: 2px;
      pointer-events: none;
    }
    .cert-header { text-align: center; margin-bottom: 36px; }
    .cert-logo { font-size: 48px; margin-bottom: 12px; }
    .cert-university { font-family: 'Playfair Display', serif; font-size: 22px; color: #1a4731; font-weight: 700; letter-spacing: .5px; }
    .cert-board { font-size: 14px; color: #4b7a5e; letter-spacing: 2px; text-transform: uppercase; margin-top: 4px; }
    .cert-divider { width: 80px; height: 3px; background: linear-gradient(90deg, #1a4731, #f5c842); margin: 20px auto; border-radius: 2px; }
    .cert-title { font-family: 'Playfair Display', serif; font-size: 32px; color: #1a4731; text-align: center; margin-bottom: 8px; font-weight: 900; }
    .cert-subtitle { font-size: 13px; color: #6b7280; text-align: center; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 40px; }
    .cert-body { text-align: center; font-size: 15px; color: #374151; line-height: 1.9; margin-bottom: 36px; }
    .cert-name { font-family: 'Playfair Display', serif; font-size: 28px; color: #1a4731; display: block; margin: 12px 0; font-weight: 700; border-bottom: 1.5px solid #2d7a4f; padding-bottom: 8px; display: inline-block; }
    .cert-details { background: #f0f7f3; border-radius: 8px; padding: 24px 32px; margin: 28px 0; text-align: left; }
    .cert-details table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .cert-details td { padding: 8px 12px; color: #374151; }
    .cert-details td:first-child { font-weight: 600; color: #1a4731; width: 200px; }
    .cert-footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 48px; }
    .cert-sig { text-align: center; min-width: 180px; }
    .cert-sig-line { border-top: 1.5px solid #1a4731; padding-top: 8px; font-size: 13px; color: #1a4731; font-weight: 600; }
    .cert-sig-role { font-size: 12px; color: #6b7280; margin-top: 2px; }
    .cert-seal { text-align: center; }
    .cert-seal-circle { width: 90px; height: 90px; border: 3px solid #1a4731; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 0 auto; background: #f0f7f3; }
    .cert-seal-circle span:first-child { font-size: 24px; }
    .cert-seal-circle span:last-child { font-size: 9px; color: #1a4731; font-weight: 700; letter-spacing: .5px; text-align: center; line-height: 1.3; }
    .cert-number { text-align: center; font-size: 11px; color: #9ca3af; margin-top: 24px; letter-spacing: 1px; }
    @media print {
      body { background: #fff; padding: 0; }
      .cert-actions { display: none; }
      .certificate { border: 2px solid #1a4731; box-shadow: none; }
      .cert-wrap { max-width: 100%; }
    }
  </style>
</head>
<body>
<div class="cert-wrap">

  <div class="cert-actions">
    <a href="<?= $user['role'] === 'researcher' ? APP_URL . '/my-submissions.php' : APP_URL . '/submission-detail.php?id=' . $id ?>" class="back">← Back</a>
    <a href="#" onclick="window.print();return false;">🖨 Print / Save as PDF</a>
  </div>

  <div class="certificate">
    <div class="cert-border"></div>

    <div class="cert-header">
      <div class="cert-logo">🌿</div>
      <div class="cert-university">Cavite State University</div>
      <div class="cert-board">Ethics Review Board</div>
    </div>

    <div class="cert-divider"></div>

    <div class="cert-title">Certificate of Approval</div>
    <div class="cert-subtitle">Research Ethics Protocol</div>

    <div class="cert-body">
      This is to certify that the research ethics protocol submitted by
      <br>
      <span class="cert-name"><?= sanitize($sub['proponent_name']) ?></span>
      <br>
      has been reviewed and evaluated in accordance with the ethical standards
      of Cavite State University and is hereby granted approval for implementation.
    </div>

    <div class="cert-details">
      <table>
        <tr>
          <td>Tracking Number</td>
          <td><strong style="color:#1a4731;"><?= sanitize($sub['tracking_number']) ?></strong></td>
        </tr>
        <tr>
          <td>Submission Type</td>
          <td><?= sanitize($typeLabels[$sub['submission_type']] ?? $sub['submission_type']) ?></td>
        </tr>
        <tr>
          <td>College / Unit</td>
          <td><?= sanitize($sub['college']) ?></td>
        </tr>
        <tr>
          <td>Designation</td>
          <td><?= sanitize($sub['designation']) ?></td>
        </tr>
        <tr>
          <td>Date of Approval</td>
          <td><?= $approvalDate ?></td>
        </tr>
        <?php if ($sub['reviewer_name']): ?>
        <tr>
          <td>Reviewed By</td>
          <td><?= sanitize($sub['reviewer_name']) ?></td>
        </tr>
        <?php endif; ?>
      </table>
    </div>

    <div class="cert-footer">
      <div class="cert-sig">
        <div class="cert-sig-line">ERB Chairperson</div>
        <div class="cert-sig-role">Cavite State University</div>
      </div>
      <div class="cert-seal">
        <div class="cert-seal-circle">
          <span>🌿</span>
          <span>CvSU ERB APPROVED</span>
        </div>
      </div>
      <div class="cert-sig">
        <div class="cert-sig-line">ERB Secretary</div>
        <div class="cert-sig-role">Cavite State University</div>
      </div>
    </div>

    <div class="cert-number">Certificate No. <?= $certNumber ?> · Issued: <?= $approvalDate ?> · EthicsFlow v1.0</div>
  </div>

</div>
</body>
</html>
