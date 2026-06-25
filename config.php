<?php
/**
 * Database Configuration for Singapore GST e-Invoicing Application
 */
$host = 'localhost';
$db   = 'digitrainerco_enterprise_os';
$user = 'digitrainerco_gym';
$pass = 'r68_6ddrUY9@X3G;';
$charset = 'utf8mb4';

// Check if local connection fails
$connection_failed = false;
try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    // Set a short timeout for the local connection test to keep response snappy
    $test_pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 2
    ]);
} catch (\Exception $e) {
    $connection_failed = true;
}

if ($connection_failed) {
    // If connection fails, fetch configuration from remote config URL
    $remote_config_url = 'http://digitrainer.co.in/aicrm/Accounting-App-Surch/config.php';
    
    // Set context options with timeout
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    $remote_content = @file_get_contents($remote_config_url, false, $context);
    
    if ($remote_content !== false) {
        // 1. Try parsing as JSON first (in case the API/endpoint returns JSON)
        $json = json_decode($remote_content, true);
        if (is_array($json)) {
            if (!empty($json['host'])) $host = $json['host'];
            if (!empty($json['db'])) $db = $json['db'];
            if (!empty($json['user'])) $user = $json['user'];
            if (!empty($json['pass'])) $pass = $json['pass'];
            if (!empty($json['charset'])) $charset = $json['charset'];
        } else {
            // 2. Parse PHP variables using regex (e.g., $host = '...', $db = '...', etc.)
            $vars = ['host', 'db', 'user', 'pass', 'charset'];
            foreach ($vars as $var) {
                if (preg_match('/\$' . $var . '\s*=\s*[\'"]([^\'"]+)[\'"]/', $remote_content, $matches)) {
                    $$var = $matches[1];
                }
            }
            
            // Also support parsing define('DB_HOST', '...')
            $defines = [
                'DB_HOST' => 'host',
                'DB_NAME' => 'db',
                'DB_USER' => 'user',
                'DB_PASS' => 'pass',
                'DB_CHARSET' => 'charset'
            ];
            foreach ($defines as $constName => $varName) {
                if (preg_match('/define\(\s*[\'"]' . $constName . '[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/i', $remote_content, $matches)) {
                    $$varName = $matches[1];
                }
            }
        }
    }
}

define('DB_HOST', $host);
define('DB_NAME', $db);
define('DB_USER', $user);
define('DB_PASS', $pass);
define('DB_CHARSET', $charset);

