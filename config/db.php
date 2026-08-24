<?php

if (!headers_sent()) {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

    if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] === "OPTIONS") {
        http_response_code(200);
        exit;
    }
}

global $conn;

// Detect environment based on OS (Windows = Local XAMPP, Linux = Vercel Cloud)
$isLocalXampp = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

if ($isLocalXampp) {
    // Local Windows XAMPP environment
    $envPath = dirname(__DIR__) . "/.env";
    $env = file_exists($envPath) ? (@parse_ini_file($envPath) ?: []) : [];
    
    $host = $env["DB_HOST"] ?? "127.0.0.1";
    $user = $env["DB_USER"] ?? "root";
    $pass = $env["DB_PASS"] ?? "";
    $name = $env["DB_NAME"] ?? "ecommerce__website";
    $port = (int)($env["DB_PORT"] ?? 3306);
} else {
    // Vercel Production Serverless Linux environment -> TiDB Cloud
    $envHost = getenv("DB_HOST") ?: ($_ENV["DB_HOST"] ?? ($_SERVER["DB_HOST"] ?? ""));
    
    if (empty($envHost) || $envHost === "127.0.0.1" || $envHost === "localhost") {
        $host = "gateway01.ap-southeast-1.prod.aws.tidbcloud.com";
        $user = "3ZHmKaANm6brJym.root";
        $pass = "IcJIp8pu8BZvNCPv";
        $name = "test";
        $port = 4000;
    } else {
        $host = $envHost;
        $user = getenv("DB_USER") ?: ($_ENV["DB_USER"] ?? ($_SERVER["DB_USER"] ?? "3ZHmKaANm6brJym.root"));
        $pass = getenv("DB_PASS") ?: ($_ENV["DB_PASS"] ?? ($_SERVER["DB_PASS"] ?? "IcJIp8pu8BZvNCPv"));
        $name = getenv("DB_NAME") ?: ($_ENV["DB_NAME"] ?? ($_SERVER["DB_NAME"] ?? "test"));
        $port = (int)(getenv("DB_PORT") ?: ($_ENV["DB_PORT"] ?? ($_SERVER["DB_PORT"] ?? 4000)));
    }
}


mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($conn) || !($conn instanceof mysqli) || @$conn->ping() === false) {
    try {
        $conn = mysqli_init();
        if ($conn) {
            if ($host !== "localhost" && $host !== "127.0.0.1") {
                $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
                @$conn->real_connect($host, $user, $pass, $name, $port, NULL, MYSQLI_CLIENT_SSL);
            } else {
                @$conn->real_connect($host, $user, $pass, $name, $port);
            }
        }

        if (!$conn || $conn->connect_error) {
            $conn = @new mysqli($host, $user, $pass, $name, $port);
        }

        if ($conn->connect_error) {
            die("<div style='padding:2rem; font-family:sans-serif; color:#dc2626; background:#fee2e2; border-radius:8px; margin:2rem;'>
                <h2>⚠️ Database Connection Error</h2>
                <p><strong>Reason:</strong> " . htmlspecialchars($conn->connect_error) . "</p>
                <p><strong>Attempted Host:</strong> " . htmlspecialchars($host) . "</p>
                <p><strong>Attempted User:</strong> " . htmlspecialchars($user) . "</p>
                <p><strong>Attempted DB:</strong> " . htmlspecialchars($name) . "</p>
                <p><strong>Attempted Port:</strong> " . $port . "</p>
            </div>");
        }
    } catch (\Throwable $e) {
        die("<div style='padding:2rem; font-family:sans-serif; color:#dc2626;'>Unable to connect to database: " . htmlspecialchars($e->getMessage()) . "</div>");
    }
}