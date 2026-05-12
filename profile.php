<?php
// profile.php
require_once __DIR__ . '/includes/config.php';
requireLogin();

$user = currentUser();
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName    = trim($_POST['full_name'] ?? '');
    $college     = trim($_POST['college'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $newPass     = $_POST['new_password'] ?? '';
    $newPassCfm  = $_POST['new_password_confirm'] ?? '';

    if (!$fullName) {
        $error = 'Full name is required.';
    } else {
        $pdo = getPDO();
        if ($newPass) {
            if (strlen($newPass) < 8) {
                $error = 'New password must be at least 8 characters.';
            } elseif ($newPass !== $newPassCfm) {
                $error = 'New passwords do not match.';
            } else {
                $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare('UPDATE users SET full_name=?, college=?, designation=?, password_hash=? WHERE id=?')
                    ->execute([$fullName, $college, $designation, $hash, $user['id']]);
                $msg = 'Profile and password updated.';
            }
        } else {
            $pdo->prepare('UPDATE users SET full_name=?, college=?, designation=? WHERE id=?')
                ->execute([$fullName, $college, $designation, $user['id']]);
            $msg = 'Profile updated successfully.';
        }
        $user = currentUser(); // refresh
    }
}

$pageTitle = 'My Profile';
include __DIR__ . '/includes/header.php';
?>

<main class="page-main">
<div class="auth-page" style="align-items:flex-start;padding-top:60px;">
  <div class="auth-card" style="max-width:520px;">
    <div class="auth-logo">
      <div class="profile-avatar-lg" style="width:60px;height:60px;font-size:24px;margin:0 auto 14px;">
        <?= strtoupper(substr($user['full_name'],0,1)) ?>
      </div>
      <h2><?= sanitize($user['full_name']) ?></h2>
      <p><?= sanitize($user['email']) ?></p>
      <span class="role-badge role-<?= $user['role'] ?>" style="margin-top:6px;"><?= ucfirst($user['role']) ?></span>
    </div>

    <?php if ($msg):  ?><div class="alert alert-success">✅ <?= sanitize($msg) ?></div><?php endif; ?>
    <?php if ($error):?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label">Full Name <span class="req">*</span></label>
        <input type="text" name="full_name" class="form-control"
               value="<?= sanitize($user['full_name']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled>
        <p class="form-hint">Email cannot be changed.</p>
      </div>

      <div class="form-group">
        <label class="form-label">College / Campus / Unit</label>
        <input type="text" name="college" class="form-control"
               value="<?= sanitize($user['college'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Designation</label>
        <input type="text" name="designation" class="form-control"
               value="<?= sanitize($user['designation'] ?? '') ?>">
      </div>

      <hr style="margin:24px 0;border-color:var(--neutral-100);">
      <p style="font-size:13px;font-weight:600;color:var(--neutral-700);margin-bottom:16px;">Change Password (leave blank to keep current)</p>

      <div class="form-group">
        <label class="form-label">New Password</label>
        <input type="password" name="new_password" class="form-control" placeholder="Min. 8 characters">
      </div>

      <div class="form-group">
        <label class="form-label">Confirm New Password</label>
        <input type="password" name="new_password_confirm" class="form-control" placeholder="Re-enter new password">
      </div>

      <button type="submit" class="btn btn-green btn-full">Save Changes</button>
    </form>
  </div>
</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
