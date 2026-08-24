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

// 1. Read environment variables from Vercel / Server
$host = getenv("DB_HOST") ?: ($_ENV["DB_HOST"] ?? ($_SERVER["DB_HOST"] ?? null));
$user = getenv("DB_USER") ?: ($_ENV["DB_USER"] ?? ($_SERVER["DB_USER"] ?? null));
$pass = getenv("DB_PASS") ?: ($_ENV["DB_PASS"] ?? ($_SERVER["DB_PASS"] ?? null));
$name = getenv("DB_NAME") ?: ($_ENV["DB_NAME"] ?? ($_SERVER["DB_NAME"] ?? null));
$port = getenv("DB_PORT") ?: ($_ENV["DB_PORT"] ?? ($_SERVER["DB_PORT"] ?? null));

// 2. Detect if running online on Vercel vs Local XAMPP
$serverHost = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
$isOnline = ($serverHost !== '' && !str_contains($serverHost, 'localhost') && !str_contains($serverHost, '127.0.0.1'));

if ($isOnline && !$host) {
    // Automatic TiDB Cloud Fallback for Vercel Online Environment
    $host = "gateway01.ap-southeast-1.prod.aws.tidbcloud.com";
    $user = "3ZHmKaANm6brJym.root";
    $pass = "IcJIp8pu8BZvNCPv";
    $name = "test";
    $port = 4000;
} elseif (!$isOnline) {
    // Local XAMPP Fallback
    $envPath = dirname(__DIR__) . "/.env";
    if (file_exists($envPath)) {
        $env = @parse_ini_file($envPath) ?: [];
        $host = $host ?: ($env["DB_HOST"] ?? "127.0.0.1");
        $user = $user ?: ($env["DB_USER"] ?? "root");
        $pass = $pass !== null ? $pass : ($env["DB_PASS"] ?? "");
        $name = $name ?: ($env["DB_NAME"] ?? "ecommerce__website");
        $port = $port ?: ($env["DB_PORT"] ?? 3306);
    }
}

$host = $host ?: "localhost";
$user = $user ?: "root";
$pass = $pass !== null ? $pass : "";
$name = $name ?: "test";
$port = (int)($port ?: 3306);

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