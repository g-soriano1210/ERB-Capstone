<?php
// register.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/mailer.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName    = trim($_POST['full_name'] ?? '');
    $email       = strtolower(trim($_POST['email'] ?? ''));
    $password    = $_POST['password'] ?? '';
    $passwordCfm = $_POST['password_confirm'] ?? '';
    $college     = trim($_POST['college'] ?? '');
    $designation = trim($_POST['designation'] ?? '');

    if (!str_ends_with($email, '@' . ALLOWED_EMAIL_DOMAIN)) {
        $error = 'Only @' . ALLOWED_EMAIL_DOMAIN . ' email addresses are allowed to register.';
    } elseif (strlen($fullName) < 3) {
        $error = 'Please enter your full name.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $passwordCfm) {
        $error = 'Passwords do not match.';
    } else {
        $pdo   = getPDO();
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'This email address is already registered. Please log in.';
        } else {
            $hash  = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $token = bin2hex(random_bytes(32));
            $stmt  = $pdo->prepare('INSERT INTO users (email, password_hash, full_name, college, designation, is_verified, verification_token) VALUES (?, ?, ?, ?, ?, 0, ?)');
            try {
                $stmt->execute([$email, $hash, $fullName, $college, $designation, $token]);
                $newUser = ['email' => $email, 'full_name' => $fullName, 'verification_token' => $token];
                sendVerificationEmail($newUser);
                $success = 'Account created! A verification link has been sent to <strong>' . sanitize($email) . '</strong>. Please check your inbox (and spam folder) and click the link to activate your account.';
            } catch (PDOException $e) {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
include __DIR__ . '/includes/header.php';
?>

<main class="page-main">
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="auth-logo-icon">🌿</div>
      <h2>Create Account</h2>
      <p>CvSU Ethics Review Board System</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error">⚠️ <?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success">✅ <?= $success ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label">Full Name <span class="req">*</span></label>
        <input type="text" name="full_name" class="form-control"
               placeholder="Surname, First Name, M.I."
               value="<?= sanitize($_POST['full_name'] ?? '') ?>"
               required>
        <p class="form-hint">Format: Dela Cruz, Juan A.</p>
      </div>

      <div class="form-group">
        <label class="form-label">CvSU Email Address <span class="req">*</span></label>
        <input type="email" name="email" class="form-control"
               placeholder="you@cvsu.edu.ph"
               value="<?= sanitize($_POST['email'] ?? '') ?>"
               required>
        <p class="form-hint">Only @cvsu.edu.ph addresses are accepted.</p>
      </div>

      <div class="form-group">
        <label class="form-label">College / Campus / Unit</label>
        <input type="text" name="college" class="form-control"
               placeholder="e.g. College of Arts and Sciences"
               value="<?= sanitize($_POST['college'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Designation</label>
        <input type="text" name="designation" class="form-control"
               placeholder="e.g. Assistant Professor II"
               value="<?= sanitize($_POST['designation'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Password <span class="req">*</span></label>
        <input type="password" name="password" class="form-control"
               placeholder="Minimum 8 characters" required>
      </div>

      <div class="form-group">
        <label class="form-label">Confirm Password <span class="req">*</span></label>
        <input type="password" name="password_confirm" class="form-control"
               placeholder="Re-enter password" required>
      </div>

      <button type="submit" class="btn btn-green btn-full btn-lg" style="margin-top:8px;">
        Create Account
      </button>
    </form>
    <?php endif; ?>

    <div class="auth-footer">
      Already have an account? <a href="<?= APP_URL ?>/login.php">Sign in</a>
    </div>
  </div>
</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
