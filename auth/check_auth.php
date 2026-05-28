<?php
require_once __DIR__ . '/auth_check.php';
echo "auth_check loaded OK. role function exists = " . (function_exists('require_role') ? 'YES' : 'NO');