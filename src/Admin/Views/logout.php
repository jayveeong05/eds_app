<?php
/**
 * Logout Handler
 */
session_start();
session_unset();
session_destroy();
header('Location: /admin/');
exit;
?>
