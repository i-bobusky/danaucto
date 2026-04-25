<?php
/**
 * Účetnictví Votýpková — Contact form handler
 * Wedos / Vedos shared hosting compatible (PHP mail()).
 *
 * Behaviour:
 *  - Accepts only POST.
 *  - Validates required fields, e-mail format, GDPR consent.
 *  - Honeypot ("website") field silently blocks bots.
 *  - On success: redirect to index.html?sent=1#kontakt
 *  - On error  : redirect to index.html?error=1#kontakt
 *  - Never exposes technical errors to the user.
 */

declare(strict_types=1);

// ---------- Configuration ----------
$RECIPIENT     = 'info@ucto-pe.online';
$FROM_ADDRESS  = 'info@ucto-pe.online';            // Same domain — passes SPF on Wedos
$FROM_NAME     = 'Web Účetnictví Votýpková';
$RETURN_PATH   = 'info@ucto-pe.online';            // Used as -f parameter
$SUBJECT       = 'Nová poptávka z webu Účetnictví Votýpková';

// ---------- Helpers ----------
function safe_redirect(string $url): void {
    header('Location: ' . $url, true, 303);
    exit;
}

function clean(string $value, int $max = 2000): string {
    // Strip control chars except tab + newline; trim; cap length.
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    $value = trim($value);
    if (mb_strlen($value, 'UTF-8') > $max) {
        $value = mb_substr($value, 0, $max, 'UTF-8');
    }
    return $value;
}

function strip_header_injection(string $value): string {
    // Defense against e-mail header injection.
    return str_replace(["\r", "\n", "%0a", "%0d", "%0A", "%0D"], '', $value);
}

function encode_subject_utf8(string $subject): string {
    return '=?UTF-8?B?' . base64_encode($subject) . '?=';
}

function encode_from_utf8(string $name, string $email): string {
    return '=?UTF-8?B?' . base64_encode($name) . '?= <' . $email . '>';
}

// ---------- Method gate ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    safe_redirect('index.html#kontakt');
}

// ---------- Honeypot ----------
$honeypot = $_POST['website'] ?? '';
if ($honeypot !== '') {
    // Pretend success so bots don't retry, but DO NOT send.
    safe_redirect('index.html?sent=1#kontakt');
}

// ---------- Read & sanitize inputs ----------
$name    = clean((string)($_POST['name']    ?? ''), 200);
$email   = clean((string)($_POST['email']   ?? ''), 200);
$phone   = clean((string)($_POST['phone']   ?? ''), 60);
$service = clean((string)($_POST['service'] ?? ''), 100);
$message = clean((string)($_POST['message'] ?? ''), 5000);
$gdpr    = isset($_POST['gdpr']) && $_POST['gdpr'] !== '';

// ---------- Validate ----------
$errors = [];

if ($name === '')                                 $errors[] = 'name';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'email';
if ($message === '')                              $errors[] = 'message';
if (!$gdpr)                                       $errors[] = 'gdpr';

// Restrict service to known options
$allowedServices = [
    'Vedení účetnictví',
    'Daňové poradenství',
    'Mzdová agenda',
    'Daňová evidence',
    'Komunikace s úřady',
    'Jiný dotaz',
];
if ($service === '' || !in_array($service, $allowedServices, true)) {
    $service = 'Jiný dotaz';
}

if (!empty($errors)) {
    safe_redirect('index.html?error=1#kontakt');
}

// ---------- Build e-mail ----------
$timestamp   = date('Y-m-d H:i:s');
$ip          = $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent   = $_SERVER['HTTP_USER_AGENT'] ?? '';

$bodyLines = [
    'Nová poptávka z webu Účetnictví Votýpková',
    '------------------------------------------',
    '',
    'Jméno a příjmení : ' . $name,
    'E-mail           : ' . $email,
    'Telefon          : ' . ($phone !== '' ? $phone : '(neuvedeno)'),
    'Typ služby       : ' . $service,
    '',
    'Zpráva:',
    $message,
    '',
    '------------------------------------------',
    'Čas odeslání : ' . $timestamp,
    'IP adresa    : ' . $ip,
    'Prohlížeč    : ' . $userAgent,
    'Source       : website contact form',
];

$body = implode("\r\n", $bodyLines);

// Header values must be free of CR/LF — strip just to be safe.
$emailSafe = strip_header_injection($email);
$nameSafe  = strip_header_injection($name);

$headers   = [];
$headers[] = 'From: '         . encode_from_utf8($FROM_NAME, $FROM_ADDRESS);
$headers[] = 'Reply-To: '     . encode_from_utf8($nameSafe, $emailSafe);
$headers[] = 'Return-Path: <' . $RETURN_PATH . '>';
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'Content-Transfer-Encoding: 8bit';
$headers[] = 'X-Mailer: PHP/' . phpversion();

$headerString = implode("\r\n", $headers);
$subjectEnc   = encode_subject_utf8($SUBJECT);

// ---------- Send ----------
// Suppress runtime warnings — we only care about boolean result.
$sent = @mail(
    $RECIPIENT,
    $subjectEnc,
    $body,
    $headerString,
    '-f' . $RETURN_PATH
);

if ($sent) {
    safe_redirect('index.html?sent=1#kontakt');
} else {
    safe_redirect('index.html?error=1#kontakt');
}
