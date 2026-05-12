<?php
// logout.php
require_once __DIR__ . '/includes/config.php';
startSession();
session_destroy();
header('Location: ' . APP_URL . '/login.php');
exit;
