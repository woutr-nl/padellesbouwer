<?php
// Test file to check URL rewriting and parameters
echo "Test file accessed successfully!\n";
echo "GET parameters: " . print_r($_GET, true) . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'not set') . "\n";
?> 