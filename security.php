<?php
// FILE: security.php
// Modulo centrale di sicurezza per il sito della Dott.ssa Martina Violo

// 1. CHIAVI SEGRETE PER FIRME HMAC E CRON
if (!defined('APP_SECRET_KEY')) {
    define('APP_SECRET_KEY', 'martina_violo_sec_key_9f83b271c08d4a5e6b1287e14');
}
if (!defined('CRON_SECRET_KEY')) {
    define('CRON_SECRET_KEY', 'cron_promemoria_token_883a91bc74d02');
}

// 2. CONFIGURAZIONE SICURA DELLA SESSIONE
function start_secure_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

// 3. CONTROLLO AUTENTICAZIONE ADMIN
function require_admin_login(): void {
    start_secure_session();
    if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
        header("Location: login.php");
        exit;
    }
}

// 4. GESTIONE TOKEN CSRF
function get_csrf_token(): string {
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    $token = htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function verify_csrf_token(?string $token = null): bool {
    start_secure_session();
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf_token(): void {
    if (!verify_csrf_token()) {
        http_response_code(403);
        die("<div style='font-family:sans-serif; text-align:center; padding:50px; color:#c0392b;'>
                <h2>Richiesta non valida (Token di sicurezza scaduto o non valido).</h2>
                <p><a href='javascript:history.back()'>Torna indietro</a> e ricarica la pagina.</p>
             </div>");
    }
}

// 5. FIRME CRITTOGRAFICHE HMAC PER LINK VIA EMAIL (1-Click sicuro)
function generate_action_token(string $action, int|string $id): string {
    $payload = $action . ':' . (string)$id;
    return hash_hmac('sha256', $payload, APP_SECRET_KEY);
}

function verify_action_token(string $action, int|string $id, ?string $providedToken): bool {
    if (empty($providedToken)) {
        return false;
    }
    $expected = generate_action_token($action, $id);
    return hash_equals($expected, $providedToken);
}

// 6. RATE LIMITING LOGIN (Protezione contro Brute Force)
function check_login_rate_limit(): array {
    start_secure_session();
    $maxAttempts = 5;
    $lockoutTime = 15 * 60; // 15 minuti

    $attempts  = $_SESSION['login_attempts'] ?? 0;
    $lockUntil = $_SESSION['login_lock_until'] ?? 0;
    $now       = time();

    if ($lockUntil > $now) {
        $remaining = ceil(($lockUntil - $now) / 60);
        return [
            'allowed' => false,
            'message' => "Troppi tentativi falliti. Riprova tra $remaining minuto/i."
        ];
    }

    if ($lockUntil > 0 && $lockUntil <= $now) {
        $_SESSION['login_attempts'] = 0;
        unset($_SESSION['login_lock_until']);
    }

    return ['allowed' => true, 'message' => ''];
}

function record_failed_login(): void {
    start_secure_session();
    $maxAttempts = 5;
    $lockoutTime = 15 * 60;

    $attempts = ($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['login_attempts'] = $attempts;

    if ($attempts >= $maxAttempts) {
        $_SESSION['login_lock_until'] = time() + $lockoutTime;
    }
}

function reset_login_attempts(): void {
    start_secure_session();
    unset($_SESSION['login_attempts'], $_SESSION['login_lock_until']);
}
