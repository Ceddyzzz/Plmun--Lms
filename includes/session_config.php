<?php
// Session configuration file - include this BEFORE session_start()

// Security settings for sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

// Session lifetime (in seconds)
ini_set('session.gc_maxlifetime', 1800); // 30 minutes
ini_set('session.cookie_lifetime', 0); // Until browser closes

// Session security
ini_set('session.use_trans_sid', 0); // Don't use URL-based sessions
?>
