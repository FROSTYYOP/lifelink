<?php
/**
 * LifeLink - Database Configuration & PDO Connection
 * College Mini-Project (Zero-Control-Panel setup)
 */

// Database configuration settings
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'lifelink');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''); // default empty or set your password
define('DB_CHARSET', 'utf8mb4');

// Environment: automatically 'development' on localhost, 'production' on live server
$isLocalHost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', 'localhost:8000', '127.0.0.1', '127.0.0.1:8000']) || (php_sapi_name() === 'cli');
define('APP_ENV', getenv('APP_ENV') ?: ($isLocalHost ? 'development' : 'production'));

/**
 * Returns the PDO database instance.
 * Implements clean error diagnostics for college presentations/vivas.
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Auto-recovery on Windows: if MySQL is stopped, attempt background start and retry once
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && ($e->getCode() == 2002 || str_contains($e->getMessage(), 'actively refused'))) {
            $userProfile = getenv('USERPROFILE') ?: 'C:\\Users\\Admin';
            $dataDir = $userProfile . '\\mysql-data';
            if (is_dir($dataDir)) {
                @pclose(@popen("start /B mysqld --datadir=\"{$dataDir}\"", "r"));
                sleep(2);
                try {
                    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                    return $pdo;
                } catch (PDOException $retryEx) {
                    $e = $retryEx;
                }
            }
        }

        // Render a helpful, developer-friendly diagnostic screen instead of a raw crash
        http_response_code(500);
        $errorMsg = htmlspecialchars($e->getMessage());
        $dbHost = DB_HOST;
        $dbPort = DB_PORT;
        $dbName = DB_NAME;
        $dbUser = DB_USER;

        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeLink - Database Connection Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; }
        .error-card { border-radius: 12px; border-top: 5px solid #800020; box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
        .code-box { background: #1e293b; color: #38bdf8; padding: 14px; border-radius: 8px; font-family: monospace; font-size: 0.9rem; }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100 py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card error-card p-4 p-md-5 bg-white">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger me-3">
                            <i class="bi bi-database-exclamation fs-1" style="color: #800020;"></i>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold" style="color: #800020;">Database Connection Required</h3>
                            <p class="text-muted mb-0">LifeLink could not connect to your MySQL database server.</p>
                        </div>
                    </div>

                    <div class="alert alert-danger" role="alert">
                        <strong>Error Details:</strong> {$errorMsg}
                    </div>

                    <h5 class="fw-bold mt-4 mb-3"><i class="bi bi-tools me-2 text-danger"></i>Quick Fix Steps (VS Code / Terminal):</h5>
                    <ol class="list-group list-group-numbered mb-4">
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">1. Make sure your MySQL Server is running</div>
                                Standalone MySQL service can be started in PowerShell with:
                                <div class="code-box mt-2">Start-Service MySQL # or mysqld</div>
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">2. Import the LifeLink database & seed data</div>
                                Open terminal in the project root and run:
                                <div class="code-box mt-2">Get-Content database/schema.sql | mysql -u root -p</div>
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">3. Check credentials in <code>config/db.php</code></div>
                                Current settings: Host: <code>{$dbHost}:{$dbPort}</code> | DB: <code>{$dbName}</code> | User: <code>{$dbUser}</code>
                            </div>
                        </li>
                    </ol>

                    <div class="text-center">
                        <a href="" class="btn btn-danger px-4 py-2" style="background-color: #800020; border-color: #800020;">
                            <i class="bi bi-arrow-clockwise me-1"></i> Retry Connection
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
        exit;
    }
}

// Global PDO reference for procedural simplicity
$pdo = getDBConnection();
