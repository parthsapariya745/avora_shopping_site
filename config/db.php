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

$envPath = dirname(__DIR__) . "/.env";
$env = [];
if (file_exists($envPath)) {
    $env = @parse_ini_file($envPath) ?: [];
}

$host = getenv("DB_HOST") ?: ($_ENV["DB_HOST"] ?? ($_SERVER["DB_HOST"] ?? ($env["DB_HOST"] ?? "localhost")));
$user = getenv("DB_USER") ?: ($_ENV["DB_USER"] ?? ($_SERVER["DB_USER"] ?? ($env["DB_USER"] ?? "root")));
$pass = getenv("DB_PASS") ?: ($_ENV["DB_PASS"] ?? ($_SERVER["DB_PASS"] ?? ($env["DB_PASS"] ?? "")));
$name = getenv("DB_NAME") ?: ($_ENV["DB_NAME"] ?? ($_SERVER["DB_NAME"] ?? ($env["DB_NAME"] ?? "")));
$port = getenv("DB_PORT") ?: ($_ENV["DB_PORT"] ?? ($_SERVER["DB_PORT"] ?? ($env["DB_PORT"] ?? 3306)));
$port = (int)$port;

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
            die("<div style='padding:2rem; font-family:sans-serif; color:#dc2626;'>Unable to connect to database: " . htmlspecialchars($conn->connect_error) . " (Host: " . htmlspecialchars($host) . ", User: " . htmlspecialchars($user) . ", DB: " . htmlspecialchars($name) . ", Port: " . $port . ")</div>");
        }
    } catch (\Throwable $e) {
        die("<div style='padding:2rem; font-family:sans-serif; color:#dc2626;'>Unable to connect to database: " . htmlspecialchars($e->getMessage()) . "</div>");
    }
}