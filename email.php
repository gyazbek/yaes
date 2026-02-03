<?php
/*

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

http://www.gnu.org/licenses/ 

@Project YAES - Yet Another Email Script
@Author Gui Yazbek
@Version 1.1

*/

// Uncomment the following if you wish to debug the script by viewing all php errors
// ini_set('display_startup_errors',1);
// ini_set('display_errors',1);
// error_reporting(-1);

// Change these values for your deployment
$to            = "youremail@example.com";
$subject       = "Message Subject";
$fromOverride  = "no-reply@yourdomain.com"; // Use a domain-owned From for DMARC alignment


// Respond with an HTTP status and optional JSON payload
function respond(int $status, array $payload = null): void {
    http_response_code($status);
    if ($payload !== null) {
        header('Content-Type: application/json');
        echo json_encode($payload);
    }
    exit;
}


// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Allow: POST');
    respond(405, array("errors" => array("Method Not Allowed")));
}

// Extract input supporting JSON or form-url-encoded bodies
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$rawBody = file_get_contents('php://input');
$maxBodyBytes = 32768; // ~32 KB guardrail for JSON payloads
$input = array();

if (stripos($contentType, 'application/json') !== false) {
    if (strlen($rawBody) > $maxBodyBytes) {
        respond(413, array("errors" => array("Payload too large")));
    }
    $decoded = json_decode($rawBody, true);
    if (!is_array($decoded)) {
        respond(400, array("errors" => array("Invalid JSON payload")));
    }
    $input = $decoded;
} else {
    $input = $_POST;
}

// Validate fields
$errors = array();

$fromHeader = $fromOverride;
$replyTo = null;

$fromValue = isset($input['from']) ? trim($input['from']) : '';
if ($fromValue === '') {
    $errors[] = 'Email is required';
} elseif (preg_match('/\r|\n/', $fromValue)) {
    $errors[] = 'Email is not valid';
} else {
    $sanitized = filter_var($fromValue, FILTER_SANITIZE_EMAIL);
    if (!filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email is not valid';
    } else {
        $replyTo = $sanitized;
    }
}

if ($fromOverride !== null && !filter_var($fromOverride, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Configured fromOverride is not a valid email';
}

// Fall back to user email as From only if no override was configured
if ($fromOverride === null) {
    $fromHeader = $replyTo;
}

$message = isset($input['message']) ? trim($input['message']) : '';
if ($message === '') {
    $errors[] = 'Message is required';
} else {
    $message = strip_tags($message);
    $maxLength = 5000;
    $lengthFn = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
    if ($lengthFn($message) > $maxLength) {
        $errors[] = 'Message is too long';
    }
}

if (!empty($errors)) {
    respond(400, array("errors" => $errors));
}

// Build headers with UTF-8 and no HTML content
$headers  = 'From: ' . $fromHeader . "\r\n";
$headers .= 'Reply-To: ' . $replyTo . "\r\n";
$headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";
$headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
$headers .= 'MIME-Version: 1.0' . "\r\n";

if (mail($to, $subject, $message, $headers)) {
    respond(201, array("status" => "sent"));
}

error_log('YAES mail() failed for recipient ' . $to);
respond(500, array("errors" => array('Unable to send email at this time, please try again later')));
?>