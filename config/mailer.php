<?php

/**
 * AVORA E-Commerce - SMTP Mailer & Inquiry Handler
 * Direct SMTP integration for delivering contact inquiries to parthsapariyait7@gmail.com
 */

function getMailerConfig() {
    $envPath = dirname(__DIR__) . "/.env";
    $env = file_exists($envPath) ? (@parse_ini_file($envPath) ?: []) : [];

    return [
        'host'       => getenv('SMTP_HOST') ?: ($env['SMTP_HOST'] ?? 'smtp.gmail.com'),
        'port'       => (int)(getenv('SMTP_PORT') ?: ($env['SMTP_PORT'] ?? 587)),
        'user'       => getenv('SMTP_USER') ?: ($env['SMTP_USER'] ?? 'parthsapariyait7@gmail.com'),
        'pass'       => getenv('SMTP_PASS') ?: ($env['SMTP_PASS'] ?? ''),
        'encryption' => strtolower(getenv('SMTP_ENCRYPTION') ?: ($env['SMTP_ENCRYPTION'] ?? 'tls')),
        'from_name'  => getenv('SMTP_FROM_NAME') ?: ($env['SMTP_FROM_NAME'] ?? 'AVORA Contact Inquiry'),
        'receiver'   => getenv('INQUIRY_RECEIVER_EMAIL') ?: ($env['INQUIRY_RECEIVER_EMAIL'] ?? 'parthsapariyait7@gmail.com')
    ];
}

/**
 * Saves inquiry into the database 'inquiries' table.
 */
function saveInquiryToDatabase($name, $email, $subject, $message) {
    global $conn;

    if (!isset($conn) || !($conn instanceof mysqli)) {
        $dbFile = __DIR__ . '/db.php';
        if (file_exists($dbFile)) {
            require_once $dbFile;
        }
    }

    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        $createSql = "CREATE TABLE IF NOT EXISTS inquiries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NULL,
            message TEXT NOT NULL,
            status ENUM('new', 'read', 'replied') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        @$conn->query($createSql);

        $stmt = $conn->prepare("INSERT INTO inquiries (name, email, subject, message) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $name, $email, $subject, $message);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }
    }
    return false;
}

/**
 * Low-level SMTP Socket Mailer supporting SSL / TLS & AUTH LOGIN
 */
function sendSmtpMail($to, $subject, $htmlBody, $replyToEmail = '', $replyToName = '') {
    $config = getMailerConfig();
    $host = $config['host'];
    $port = (int)$config['port'];
    $user = trim($config['user']);
    $pass = trim($config['pass']);
    $encryption = strtolower($config['encryption']);
    $fromEmail = !empty($user) ? $user : 'parthsapariyait7@gmail.com';
    $fromName = $config['from_name'];

    if (empty($host)) {
        return ['success' => false, 'message' => 'SMTP Host is not configured.'];
    }

    $timeout = 12;
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    if ($encryption === 'ssl' || $port === 465) {
        $socket = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
    } else {
        $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
    }

    if (!$socket) {
        return ['success' => false, 'message' => "Could not connect to SMTP server {$host}:{$port} ($errstr)"];
    }

    $readResponse = function($socket) {
        $response = "";
        while ($line = @fgets($socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === " ") {
                break;
            }
        }
        return $response;
    };

    $sendCommand = function($socket, $command, $expectedCode = 250) use ($readResponse) {
        fputs($socket, $command . "\r\n");
        $response = $readResponse($socket);
        $code = (int)substr($response, 0, 3);
        if ($expectedCode && $code !== $expectedCode) {
            return ['status' => false, 'code' => $code, 'response' => $response];
        }
        return ['status' => true, 'code' => $code, 'response' => $response];
    };

    $greeting = $readResponse($socket);
    if ((int)substr($greeting, 0, 3) !== 220) {
        fclose($socket);
        return ['success' => false, 'message' => 'Invalid SMTP greeting banner: ' . trim($greeting)];
    }

    $clientDomain = gethostname() ?: 'localhost';
    $res = $sendCommand($socket, "EHLO " . $clientDomain, 250);
    if (!$res['status']) {
        fclose($socket);
        return ['success' => false, 'message' => 'EHLO command failed: ' . trim($res['response'])];
    }

    // STARTTLS Upgrade for Port 587 or TLS mode
    if ($encryption === 'tls' || ($encryption !== 'ssl' && $port === 587)) {
        $res = $sendCommand($socket, "STARTTLS", 220);
        if (!$res['status']) {
            fclose($socket);
            return ['success' => false, 'message' => 'STARTTLS negotiation failed: ' . trim($res['response'])];
        }

        $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }

        if (!@stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
            fclose($socket);
            return ['success' => false, 'message' => 'Failed to establish TLS encryption socket layer.'];
        }

        $res = $sendCommand($socket, "EHLO " . $clientDomain, 250);
        if (!$res['status']) {
            fclose($socket);
            return ['success' => false, 'message' => 'EHLO failed after TLS encryption: ' . trim($res['response'])];
        }
    }

    // Authentication if password provided
    if (!empty($pass)) {
        $res = $sendCommand($socket, "AUTH LOGIN", 334);
        if (!$res['status']) {
            fclose($socket);
            return ['success' => false, 'message' => 'AUTH LOGIN command rejected: ' . trim($res['response'])];
        }

        $res = $sendCommand($socket, base64_encode($user), 334);
        if (!$res['status']) {
            fclose($socket);
            return ['success' => false, 'message' => 'SMTP Username rejected: ' . trim($res['response'])];
        }

        $res = $sendCommand($socket, base64_encode($pass), 235);
        if (!$res['status']) {
            fclose($socket);
            return ['success' => false, 'message' => 'SMTP App Password authentication failed: ' . trim($res['response'])];
        }
    }

    // MAIL FROM
    $res = $sendCommand($socket, "MAIL FROM:<{$fromEmail}>", 250);
    if (!$res['status']) {
        fclose($socket);
        return ['success' => false, 'message' => 'MAIL FROM rejected: ' . trim($res['response'])];
    }

    // RCPT TO
    $res = $sendCommand($socket, "RCPT TO:<{$to}>", 250);
    if (!$res['status']) {
        fclose($socket);
        return ['success' => false, 'message' => 'RCPT TO rejected: ' . trim($res['response'])];
    }

    // DATA
    $res = $sendCommand($socket, "DATA", 354);
    if (!$res['status']) {
        fclose($socket);
        return ['success' => false, 'message' => 'DATA command rejected: ' . trim($res['response'])];
    }

    // Build Headers & HTML Body
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
    if (!empty($replyToEmail)) {
        $rName = !empty($replyToName) ? $replyToName : $replyToEmail;
        $headers .= "Reply-To: =?UTF-8?B?" . base64_encode($rName) . "?= <{$replyToEmail}>\r\n";
    }
    $headers .= "To: <{$to}>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "Date: " . date("r") . "\r\n";
    $headers .= "X-Mailer: AVORA-PHP-SMTP/1.0\r\n";

    $mailContent = $headers . "\r\n" . $htmlBody . "\r\n.";

    $res = $sendCommand($socket, $mailContent, 250);
    if (!$res['status']) {
        fclose($socket);
        return ['success' => false, 'message' => 'Email payload body transmission failed: ' . trim($res['response'])];
    }

    $sendCommand($socket, "QUIT", 221);
    fclose($socket);

    return [
        'success' => true,
        'message' => "Inquiry email sent successfully via SMTP to {$to}!"
    ];
}

/**
 * Higher-level function to format & send Contact Form inquiry emails
 */
function sendInquiryEmail($name, $senderEmail, $subject, $message) {
    $config = getMailerConfig();
    $recipientEmail = $config['receiver'];
    
    // Save to Database first so no message is ever lost
    $dbSaved = saveInquiryToDatabase($name, $senderEmail, $subject, $message);

    $safeName = htmlspecialchars($name);
    $safeEmail = htmlspecialchars($senderEmail);
    $safeSubject = htmlspecialchars($subject ?: 'General Inquiry');
    $safeMessage = nl2br(htmlspecialchars($message));
    $timestamp = date('F j, Y, g:i a');
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    $emailSubject = "📩 New Store Inquiry: {$safeSubject} (from {$safeName})";

    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset='utf-8'>
      <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #1e293b; }
        .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .email-header { background: #0f172a; color: #ffffff; padding: 24px 30px; text-align: center; }
        .email-header h2 { margin: 0; font-size: 22px; font-weight: 700; letter-spacing: 1px; color: #f8fafc; }
        .email-header p { margin: 6px 0 0 0; font-size: 13px; color: #94a3b8; }
        .email-body { padding: 30px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .info-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .info-label { font-weight: 700; color: #475569; width: 30%; background: #f8fafc; }
        .message-box { background: #f8fafc; border-left: 4px solid #0f172a; padding: 18px; font-size: 14px; line-height: 1.6; color: #334155; border-radius: 4px; }
        .email-footer { background: #f1f5f9; padding: 16px 30px; font-size: 12px; color: #64748b; text-align: center; border-top: 1px solid #e2e8f0; }
        .badge { display: inline-block; background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
      </style>
    </head>
    <body>
      <div class='email-container'>
        <div class='email-header'>
          <h2>AVORA E-COMMERCE</h2>
          <p>Customer Contact Inquiry Received</p>
        </div>
        <div class='email-body'>
          <p style='font-size: 15px; margin-top: 0;'>Hello <strong>Parth Sapariya</strong>,</p>
          <p style='font-size: 14px; color: #475569;'>You have received a new inquiry from your website's contact form:</p>

          <table class='info-table'>
            <tr>
              <td class='info-label'>Sender Name:</td>
              <td><strong>{$safeName}</strong></td>
            </tr>
            <tr>
              <td class='info-label'>Sender Email:</td>
              <td><a href='mailto:{$safeEmail}' style='color:#2563eb;'>{$safeEmail}</a></td>
            </tr>
            <tr>
              <td class='info-label'>Subject:</td>
              <td><span class='badge'>{$safeSubject}</span></td>
            </tr>
            <tr>
              <td class='info-label'>Received At:</td>
              <td>{$timestamp}</td>
            </tr>
            <tr>
              <td class='info-label'>IP Address:</td>
              <td>{$clientIp}</td>
            </tr>
          </table>

          <h4 style='margin: 0 0 10px 0; font-size: 14px; color: #0f172a;'>Message Content:</h4>
          <div class='message-box'>
            {$safeMessage}
          </div>

          <div style='margin-top: 24px; text-align: center;'>
            <a href='mailto:{$safeEmail}' style='display: inline-block; background: #0f172a; color: #ffffff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600;'>Reply to Customer ({$safeEmail})</a>
          </div>
        </div>
        <div class='email-footer'>
          Sent automatically by AVORA Direct SMTP Engine &bull; Destination: {$recipientEmail}
        </div>
      </div>
    </body>
    </html>
    ";

    // Attempt SMTP delivery
    $smtpResult = sendSmtpMail($recipientEmail, $emailSubject, $htmlBody, $senderEmail, $name);

    if ($smtpResult['success']) {
        return [
            'success' => true,
            'db_saved' => $dbSaved,
            'smtp_sent' => true,
            'message' => "Your inquiry has been successfully sent to {$recipientEmail}!"
        ];
    } else {
        // If SMTP pass is missing or auth error occurred
        if (empty($config['pass'])) {
            $msg = "Your inquiry has been recorded and saved successfully in the system database. (Note: To send direct SMTP emails to {$recipientEmail}, please configure SMTP_PASS in .env)";
        } else {
            $msg = "Your inquiry was saved in our system! SMTP Status: " . $smtpResult['message'];
        }
        return [
            'success' => true,
            'db_saved' => $dbSaved,
            'smtp_sent' => false,
            'smtp_error' => $smtpResult['message'],
            'message' => $msg
        ];
    }
}
