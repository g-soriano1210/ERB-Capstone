<?php
// verify.php — Email verification handler
require_once __DIR__ . '/includes/config.php';

$token = trim($_GET['token'] ?? '');
$status = 'invalid';

if ($token) {
    $pdo  = getPDO();
    $stmt = $pdo->prepare('SELECT id, is_verified FROM users WHERE verification_token = ?');
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['is_verified']) {
            $status = 'already';
        } else {
            $pdo->prepare('UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?')
                ->execute([$user['id']]);
            $status = 'success';
        }
    }
}

$pageTitle = 'Email Verification';
include __DIR__ . '/includes/header.php';
?>

<main class="page-main">
<div class="auth-page">
  <div class="auth-card" style="text-align:center;">

    <?php if ($status === 'success'): ?>
      <div style="font-size:56px;margin-bottom:16px;">✅</div>
      <h2 style="color:var(--green-800);margin-bottom:8px;">Email Verified!</h2>
      <p style="color:var(--neutral-600);margin-bottom:24px;">
        Your account has been successfully verified. You can now log in to the CvSU ERB system.
      </p>
      <a href="<?= APP_URL ?>/login.php" class="btn btn-green btn-lg">Go to Login</a>

    <?php elseif ($status === 'already'): ?>
      <div style="font-size:56px;margin-bottom:16px;">ℹ️</div>
      <h2 style="color:var(--green-800);margin-bottom:8px;">Already Verified</h2>
      <p style="color:var(--neutral-600);margin-bottom:24px;">
        Your email address has already been verified. Please log in.
      </p>
      <a href="<?= APP_URL ?>/login.php" class="btn btn-green btn-lg">Go to Login</a>

    <?php else: ?>
      <div style="font-size:56px;margin-bottom:16px;">❌</div>
      <h2 style="color:#991b1b;margin-bottom:8px;">Invalid or Expired Link</h2>
      <p style="color:var(--neutral-600);margin-bottom:24px;">
        This verification link is invalid or has already been used.
        Please register again or contact the ERB Secretariat.
      </p>
      <a href="<?= APP_URL ?>/register.php" class="btn btn-green btn-lg">Register Again</a>
    <?php endif; ?>

  </div>
</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
