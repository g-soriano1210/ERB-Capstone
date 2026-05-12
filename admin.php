<?php
// admin.php — Admin-only user & system management
require_once __DIR__ . '/includes/config.php';
requireLogin();

$user = currentUser();
if ($user['role'] !== 'admin') {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$pdo = getPDO();
$msg = '';

// Change user role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $uid  = (int)$_POST['user_id'];
    $role = $_POST['role'] ?? '';
    if (in_array($role, ['researcher','reviewer','admin']) && $uid !== $user['id']) {
        $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $uid]);
        $msg = 'User role updated.';
    }
}

// Delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = (int)$_POST['user_id'];
    if ($uid !== $user['id']) {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
        $msg = 'User deleted.';
    }
}

$users = $pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
$totalSubs = $pdo->query('SELECT COUNT(*) FROM submissions')->fetchColumn();

$pageTitle = 'Admin Panel';
$activePage = 'admin';
include __DIR__ . '/includes/header.php';
?>

<main class="page-main">

<div class="page-header">
  <div class="container">
    <h1>⚙️ Admin Panel</h1>
    <p>Manage users, roles, and system settings.</p>
  </div>
</div>

<section class="section-sm">
<div class="container">

  <?php if ($msg): ?>
    <div class="alert alert-success">✅ <?= sanitize($msg) ?></div>
  <?php endif; ?>

  <div class="stats-grid" style="margin-bottom:28px;">
    <div class="stat-card">
      <div class="stat-value"><?= count($users) ?></div>
      <div class="stat-label">Registered Users</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $totalSubs ?></div>
      <div class="stat-label">Total Submissions</div>
    </div>
  </div>

  <div class="card">
    <div class="card-body" style="padding-bottom:0;">
      <h3 style="font-size:16px;font-weight:600;margin-bottom:16px;">User Management</h3>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>College</th>
            <th>Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td style="font-size:13px;"><?= sanitize($u['full_name']) ?></td>
            <td style="font-size:12px;color:var(--neutral-500);"><?= sanitize($u['email']) ?></td>
            <td><span class="role-badge role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
            <td style="font-size:12px;color:var(--neutral-500);"><?= sanitize($u['college'] ?? '—') ?></td>
            <td style="font-size:12px;color:var(--neutral-500);"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
            <td>
              <?php if ($u['id'] !== $user['id']): ?>
              <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <form method="POST" style="display:inline-flex;gap:4px;align-items:center;">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <select name="role" class="form-control" style="padding:4px 8px;font-size:12px;width:auto;">
                    <option value="researcher" <?= $u['role']==='researcher'?'selected':'' ?>>Researcher</option>
                    <option value="reviewer"   <?= $u['role']==='reviewer'  ?'selected':'' ?>>Reviewer</option>
                    <option value="admin"      <?= $u['role']==='admin'     ?'selected':'' ?>>Admin</option>
                  </select>
                  <button type="submit" name="change_role" class="btn btn-sm" style="background:var(--green-600);color:white;">Set</button>
                </form>
                <form method="POST" onsubmit="return confirm('Delete this user?')">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" name="delete_user" class="btn btn-sm" style="background:#dc2626;color:white;">Delete</button>
                </form>
              </div>
              <?php else: ?>
                <span style="font-size:12px;color:var(--neutral-400);">(you)</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
</section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
