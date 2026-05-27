<?php
/**
 * Runtime database configuration.
 *
 * This file reads the database connection from environment variables and
 * creates a PDO connection in `$conn` (and `$db` for compatibility).
 *
 * Supported options (in order of precedence):
 *  - DATABASE_URL (full URI, e.g. mysql://user:pass@host:port/db?ssl-mode=REQUIRED)
 *  - DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, DB_SSLMODE
 *
 * The real `config/database.php` is intentionally gitignored. Keep secrets
 * in your environment (Render environment variables) and don't commit them.
 */

$database_url = getenv('DATABASE_URL');

if ($database_url && trim($database_url) !== '') {
    $uri = $database_url;
} else {
    $dbHost = getenv('DB_HOST');
    if ($dbHost && trim($dbHost) !== '') {
        $dbUser = getenv('DB_USER') ?: '';
        $dbPass = getenv('DB_PASS') ?: '';
        $dbName = getenv('DB_NAME') ?: '';
        $dbPort = getenv('DB_PORT') ?: '3306';
        $dbSsl  = getenv('DB_SSLMODE') ?: 'REQUIRED';

        // Build a DATABASE_URL-style URI so the rest of the code can parse it
        $uri = sprintf('mysql://%s:%s@%s:%s/%s?ssl-mode=%s',
            rawurlencode($dbUser), rawurlencode($dbPass), $dbHost, $dbPort, $dbName, $dbSsl
        );
    } else {
        // No environment configuration found. Fail fast with an explanatory message.
        throw new Exception("Database configuration not found. Set DATABASE_URL or DB_HOST/DB_USER/DB_PASS/DB_NAME environment variables.");
    }
}

$fields = parse_url($uri);
if ($fields === false || !isset($fields['host'])) {
    throw new Exception('Invalid DATABASE_URL format.');
}

// Determine path (database name)
$dbName = isset($fields['path']) ? ltrim($fields['path'], '/') : '';

// Resolve CA path relative to project root (assumes ca.pem is in project root)
$caPath = __DIR__ . '/../ca.pem';
if (!file_exists($caPath)) {
    // If the pem is missing, we still attempt to connect but without an explicit CA file.
    // You can upload ca.pem to the repository (non-sensitive) or supply it another way.
    $caPath = null;
}

// Build PDO DSN
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $fields['host'], $fields['port'] ?? '3306', $dbName);
// Append SSL options if CA exists - PDO MySQL uses PDO::MYSQL_ATTR_SSL_CA when creating the PDO instance

$username = $fields['user'] ?? '';
$password = $fields['pass'] ?? '';

try {
    if ($caPath) {
        // Pass SSL CA via PDO options
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_SSL_CA => $caPath,
        ];
        $conn = new PDO($dsn, $username, $password, $options);
    } else {
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        $conn = new PDO($dsn, $username, $password, $options);
    }

    // Provide $db alias for compatibility with code that used $db
    $db = $conn;
} catch (Exception $e) {
    // Bubble up a clear error so Render logs show it
    echo "Database connection error: " . $e->getMessage();
    exit(1);
}
?>