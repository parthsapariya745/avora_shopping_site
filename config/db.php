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

$host = getenv("DB_HOST") ?: ($_ENV["DB_HOST"] ?? ($_SERVER["DB_HOST"] ?? null));
$user = getenv("DB_USER") ?: ($_ENV["DB_USER"] ?? ($_SERVER["DB_USER"] ?? null));
$pass = getenv("DB_PASS") ?: ($_ENV["DB_PASS"] ?? ($_SERVER["DB_PASS"] ?? null));
$name = getenv("DB_NAME") ?: ($_ENV["DB_NAME"] ?? ($_SERVER["DB_NAME"] ?? null));
$port = getenv("DB_PORT") ?: ($_ENV["DB_PORT"] ?? ($_SERVER["DB_PORT"] ?? null));

if (!$host && file_exists(dirname(__DIR__) . "/.env")) {
    $env = @parse_ini_file(dirname(__DIR__) . "/.env") ?: [];
    $host = $env["DB_HOST"] ?? null;
    $user = $env["DB_USER"] ?? null;
    $pass = $env["DB_PASS"] ?? null;
    $name = $env["DB_NAME"] ?? null;
    $port = $env["DB_PORT"] ?? null;
}

$host = $host ?: "localhost";
$user = $user ?: "root";
$pass = $pass !== null ? $pass : "";
$name = $name ?: "";
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
                <hr style='border:0; border-top:1px solid #fca5a5; margin:1rem 0;' />
                <p><em>Note: If Host is 127.0.0.1, Vercel Environment Variables (DB_HOST, DB_USER, etc.) have not been added or saved in Vercel Dashboard yet.</em></p>
            </div>");
        }
    } catch (\Throwable $e) {
        die("<div style='padding:2rem; font-family:sans-serif; color:#dc2626;'>Unable to connect to database: " . htmlspecialchars($e->getMessage()) . "</div>");
    }
}