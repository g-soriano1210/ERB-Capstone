<?php
// includes/config.php
// ─── Database Configuration ───────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'cvsu_erb');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

// ─── App Configuration ────────────────────────────────────────────────────────
define('APP_NAME', 'CvSU ERB Online Submission');
define('APP_URL', 'http://localhost/cvsu-erb'); // Change to your server URL
define('ALLOWED_EMAIL_DOMAIN', 'cvsu.edu.ph');

// ─── Upload Configuration ─────────────────────────────────────────────────────
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 20 * 1024 * 1024); // 20MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// ─── Email Configuration (PHPMailer / SMTP) ───────────────────────────────────
// Set USE_SMTP to false to use PHP mail() function (works on most servers)
define('USE_SMTP', false);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@cvsu.edu.ph');
define('SMTP_PASS', 'your-app-password');
define('MAIL_FROM', 'noreply@cvsu.edu.ph');
define('MAIL_FROM_NAME', 'CvSU ERB System');

// ─── PDO Connection ───────────────────────────────────────────────────────────
function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// ─── Session & Security Helpers ───────────────────────────────────────────────
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict']);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function hasRole(string ...$roles): bool {
    $user = currentUser();
    return $user && in_array($user['role'], $roles, true);
}

function generateTrackingNumber(): string {
    return 'ERB-' . strtoupper(date('Ymd')) . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function redirectWith(string $url, string $key, string $value): never {
    startSession();
    $_SESSION[$key] = $value;
    header("Location: $url");
    exit;
}

function flashGet(string $key): ?string {
    startSession();
    $val = $_SESSION[$key] ?? null;
    unset($_SESSION[$key]);
    return $val;
}
