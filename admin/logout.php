<?php
require_once dirname(__DIR__) . '/includes/config.php';

// Destroy session and redirect to login
session_destroy();
header('Location: /' . $adminPath . '/login');
exit;
?> 