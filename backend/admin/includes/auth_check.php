<?php
/**
 * Authentication Check
 * Verifies admin session and redirects to login if not authenticated
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Check if session has expired (optional, 2 hours timeout)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
    session_unset();
    session_destroy();
    header('Location: index.php?timeout=1');
    exit;
}

$_SESSION['last_activity'] = time();
?>
