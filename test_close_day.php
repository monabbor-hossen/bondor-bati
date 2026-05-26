<?php
$_GET['url'] = 'inventory/closeDayView';
// Bypass header redirect by injecting user_id after session_start
// Wait, we can't easily inject between session_start and the check.
// Let's just create a dummy session file or cookie, or redefine $_SESSION after.
// Actually we can just comment out the auth check in index.php temporarily, or set bb_token cookie.
$_COOKIE['bb_token'] = 'dummy';
require 'index.php';
