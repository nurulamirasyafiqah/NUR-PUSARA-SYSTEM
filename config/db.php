<?php
// ── NUR-PUSARA Database Configuration ──
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nur_pusara');

// ── Base URL (supports both localhost/subdir AND virtual host e.g. nur-pusara.test) ──
// Strategy: find the project root by walking up from DOCUMENT_ROOT
$_docroot   = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$_scriptdir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));
// Find deepest common path between DOCUMENT_ROOT and project root
// Just detect if we're in a subfolder of docroot
$_scripturl = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
// Check if URL contains /nur-pusara/ subfolder
if (strpos($_scripturl, '/nur-pusara/') !== false) {
    $_base = '/nur-pusara';
} else {
    // Virtual host — root of domain IS the project
    $_base = '';
}
define('BASE_URL', $_base);

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");

// ── Session Start ──
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Auth Check ──
function requireLogin() {
    if (!isset($_SESSION['id_pengguna'])) {
        header("Location: " . BASE_URL . "/index.php");
        exit();
    }
}

// ── Role Check ──
function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: " . BASE_URL . "/dashboard/index.php");
        exit();
    }
}
?>
