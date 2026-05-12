<?php
// login.php
require_once __DIR__ . '/includes/config.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$error = flashGet('login_error') ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } elseif (!str_ends_with($email, '@' . ALLOWED_EMAIL_DOMAIN)) {
        $error = 'Only @' . ALLOWED_EMAIL_DOMAIN . ' email addresses are allowed.';
    } else {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Check email verification
            if (!$user['is_verified']) {
                $error = 'Your account is not yet verified. Please check your email inbox for the verification link.';
            } else {
                startSession();
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role']    = $user['role'];

                $redirects = [
                    'researcher' => APP_URL . '/index.php',
                    'reviewer'   => APP_URL . '/dashboard.php',
                    'admin'      => APP_URL . '/admin.php',
                ];
                header('Location: ' . ($redirects[$user['role']] ?? APP_URL . '/index.php'));
                exit;
            }
        } else {
            $error = 'Incorrect email or password. Please try again.';
        }
    }
}

$pageTitle  = 'Sign In';
$activePage = 'login';
include __DIR__ . '/includes/header.php';
?>

<main class="page-main">
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="auth-logo-icon">🌿</div>
      <h2>Welcome Back</h2>
      <p>CvSU Ethics Review Board System</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error">⚠️ <?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label">CvSU Email Address</label>
        <input type="email" name="email" class="form-control"
               placeholder="you@cvsu.edu.ph"
               value="<?= sanitize($_POST['email'] ?? '') ?>"
               required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control"
               placeholder="Your password" required>
      </div>

      <button type="submit" class="btn btn-green btn-full btn-lg" style="margin-top:8px;">
        Sign In
      </button>
    </form>

    <div class="auth-footer" style="margin-top:20px;">
      Don't have an account? <a href="<?= APP_URL ?>/register.php">Register here</a>
    </div>

    <div class="alert alert-info" style="margin-top:20px;font-size:12px;">
      <div>
        <strong>Demo Accounts</strong><br>
        Admin: admin@cvsu.edu.ph / password<br>
        Register your own @cvsu.edu.ph account to access as a researcher.
      </div>
    </div>
  </div>
</div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
