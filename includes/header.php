<?php
// includes/header.php
require_once __DIR__ . '/config.php';
startSession();
$user = currentUser();
$pageTitle = $pageTitle ?? APP_NAME;
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= sanitize($pageTitle) ?> — CvSU ERB</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar">
  <div class="nav-container">
    <a href="<?= APP_URL ?>/index.php" class="nav-brand">
      <div class="nav-logo">
        <span class="logo-leaf">🌿</span>
      </div>
      <div class="nav-title">
        <span class="nav-title-main">CvSU</span>
        <span class="nav-title-sub">Ethics Review Board</span>
      </div>
    </a>

    <div class="nav-links">
      <a href="<?= APP_URL ?>/index.php" class="nav-link <?= $activePage==='home'?'active':'' ?>">Home</a>

      <?php if ($user): ?>
        <?php if ($user['role'] === 'researcher'): ?>
          <a href="<?= APP_URL ?>/submit.php" class="nav-link <?= $activePage==='submit'?'active':'' ?>">Submit</a>
          <a href="<?= APP_URL ?>/my-submissions.php" class="nav-link <?= $activePage==='my-submissions'?'active':'' ?>">My Submissions</a>
        <?php elseif (in_array($user['role'], ['reviewer','admin'])): ?>
          <a href="<?= APP_URL ?>/dashboard.php" class="nav-link <?= $activePage==='dashboard'?'active':'' ?>">Dashboard</a>
          <a href="<?= APP_URL ?>/reports.php" class="nav-link <?= $activePage==='reports'?'active':'' ?>">Reports</a>
          <?php if ($user['role'] === 'admin'): ?>
            <a href="<?= APP_URL ?>/admin.php" class="nav-link <?= $activePage==='admin'?'active':'' ?>">Admin</a>
          <?php endif; ?>
        <?php endif; ?>

        <div class="nav-profile-wrap">
          <button class="nav-profile-btn" onclick="toggleProfileMenu()">
            <div class="nav-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
            <span class="nav-username"><?= sanitize(explode(' ', $user['full_name'])[0]) ?></span>
            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor"><path d="M6 8L1 3h10z"/></svg>
          </button>
          <div class="profile-dropdown" id="profileDropdown">
            <div class="profile-info">
              <div class="profile-avatar-lg"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
              <div>
                <strong><?= sanitize($user['full_name']) ?></strong>
                <span><?= sanitize($user['email']) ?></span>
                <em class="role-badge role-<?= $user['role'] ?>"><?= ucfirst($user['role']) ?></em>
              </div>
            </div>
            <hr>
            <a href="<?= APP_URL ?>/profile.php">My Profile</a>
            <a href="<?= APP_URL ?>/logout.php" class="logout-link">Sign Out</a>
          </div>
        </div>

      <?php else: ?>
        <a href="<?= APP_URL ?>/login.php" class="nav-link <?= $activePage==='login'?'active':'' ?>">Login</a>
        <a href="<?= APP_URL ?>/register.php" class="btn-nav-cta">Register</a>
      <?php endif; ?>
    </div>

    <button class="hamburger" onclick="toggleMobileNav()" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>
