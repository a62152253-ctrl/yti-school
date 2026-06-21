<?php
declare(strict_types=1);

/**
 * SecurityEnterprise.php
 *
 * A single-file security utility for PHP 8.2+.
 * Designed for small-to-medium apps that want strong defaults:
 * - secure sessions
 * - CSRF protection
 * - security headers + CSP nonce support
 * - auth helpers + RBAC
 * - rate limiting + brute-force protection
 * - password policy helpers
 * - remember-me token helpers
 * - secure file upload validation
 * - audit logging
 * - safe redirects / JSON responses
 * - input helpers
 * - PDO helper
 *
 * This file is intentionally dependency-free.
 */

final class SecurityEnterprise
{
    private const DEFAULT_SESSION_TTL = 1800; // 30 min
    private const DEFAULT_LOGIN_WINDOW = 900;  // 15 min
    private const DEFAULT_LOGIN_LIMIT = 5;
    private const DEFAULT_RATE_LIMIT_WINDOW = 60;
    private const DEFAULT_RATE_LIMIT_MAX = 30;
    private const DEFAULT_UPLOAD_MAX_BYTES = 5_242_880; // 5 MiB
    private const DEFAULT_PASSWORD_MIN_LEN = 12;

    private static array $config = [
        'app_name' => 'App',
        'session_ttl' => self::DEFAULT_SESSION_TTL,
        'session_regenerate_every' => 600, // 10 min
        'cookie_name' => 'SESSID',
        'cookie_secure_force' => null, // true/false/null(auto)
        'cookie_samesite' => 'Lax',
        'remember_me_days' => 30,
        'audit_log_file' => null,
        'use_ip_fingerprint' => true,
        'use_ua_fingerprint' => true,
        'enforce_https' => false,
        'csp_report_only' => false,
        'csp_extras' => '',
        'trusted_proxy_headers' => false,
        'time_zone' => 'UTC',
    ];

    private static ?string $cspNonce = null;



    public static function boot(): void
    {
        date_default_timezone_set((string) self::$config['time_zone']);
        self::sendSecurityHeaders();
        self::startSession();
        self::ensureCsrfToken();
    }

    public static function isHttps(): bool
    {
        $https = $_SERVER['HTTPS'] ?? null;

        if (!empty($https) && $https !== 'off') {
            return true;
        }

        if (self::$config['trusted_proxy_headers']) {
            $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
            if (strtolower((string) $proto) === 'https') {
                return true;
            }
        }

        return false;
    }



    public static function sendSecurityHeaders(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-site');

        if (self::$config['enforce_https']) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        $nonce = self::getCspNonce();
        $reportOnly = (bool) self::$config['csp_report_only'];

        $csp = [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "img-src 'self' data:",
            "font-src 'self' data:",
            "style-src 'self' 'unsafe-inline'",
            "script-src 'self' 'nonce-{$nonce}'",
            "connect-src 'self'",
            "form-action 'self'",
            "upgrade-insecure-requests",
        ];

        $extras = trim((string) self::$config['csp_extras']);
        if ($extras !== '') {
            $csp[] = $extras;
        }

        $header = implode('; ', $csp) . ';';

        if ($reportOnly) {
            header('Content-Security-Policy-Report-Only: ' . $header);
        } else {
            header('Content-Security-Policy: ' . $header);
        }
    }

    public static function getCspNonce(): string
    {
        if (self::$cspNonce === null) {
            self::$cspNonce = bin2hex(random_bytes(16));
        }

        return self::$cspNonce;
    }



    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');

        $secure = self::$config['cookie_secure_force'];
        if ($secure === null) {
            $secure = self::isHttps();
        }

        session_name((string) self::$config['cookie_name']);

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => (bool) $secure,
            'httponly' => true,
            'samesite' => (string) self::$config['cookie_samesite'],
        ]);

        session_start();

        self::enforceSessionFingerprint();
        self::enforceSessionTimeout();
        self::maybeRotateSessionId();

        $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
        $_SESSION['_created_at'] ??= time();
        $_SESSION['_last_regenerated_at'] ??= time();
    }

    public static function regenerateSession(bool $deleteOldSession = true): void
    {
        session_regenerate_id($deleteOldSession);
        $_SESSION['_last_regenerated_at'] = time();
    }

    private static function maybeRotateSessionId(): void
    {
        $every = (int) self::$config['session_regenerate_every'];
        $last = (int) ($_SESSION['_last_regenerated_at'] ?? 0);

        if ($every > 0 && (time() - $last) >= $every) {
            self::regenerateSession(true);
        }
    }

    private static function enforceSessionTimeout(): void
    {
        $ttl = (int) self::$config['session_ttl'];
        $now = time();

        if (!isset($_SESSION['_last_activity'])) {
            $_SESSION['_last_activity'] = $now;
            return;
        }

        if (($now - (int) $_SESSION['_last_activity']) > $ttl) {
            self::audit('session_expired', [
                'user_id' => $_SESSION['user_id'] ?? null,
            ]);

            self::destroySession();
            self::redirect('login.php?expired=1');
        }

        $_SESSION['_last_activity'] = $now;
    }

    private static function fingerprint(): string
    {
        $parts = [];

        if ((bool) self::$config['use_ip_fingerprint']) {
            $parts[] = $_SERVER['REMOTE_ADDR'] ?? '';
        }

        if ((bool) self::$config['use_ua_fingerprint']) {
            $parts[] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }

        return hash('sha256', implode('|', $parts));
    }

    private static function enforceSessionFingerprint(): void
    {
        $current = self::fingerprint();

        if (!isset($_SESSION['_fp'])) {
            $_SESSION['_fp'] = $current;
            return;
        }

        if (!hash_equals((string) $_SESSION['_fp'], $current)) {
            self::audit('session_fingerprint_mismatch', [
                'user_id' => $_SESSION['user_id'] ?? null,
            ]);

            self::destroySession();
            self::redirect('login.php?invalid_session=1');
        }
    }

    public static function destroySession(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 3600,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function ensureCsrfToken(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf'];
    }

    public static function csrfToken(): string
    {
        return self::ensureCsrfToken();
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::e(self::csrfToken()) . '">';
    }

    public static function csrfMetaTag(): string
    {
        return '<meta name="csrf-token" content="' . self::e(self::csrfToken()) . '">';
    }

    public static function verifyCsrf(?string $token): bool
    {
        return is_string($token) && $token !== '' && hash_equals(self::csrfToken(), $token);
    }

    public static function requireCsrf(?string $token = null): void
    {
        $token ??= self::postString('csrf_token', '');

        if (!self::verifyCsrf($token)) {
            self::audit('csrf_failed', [
                'path' => $_SERVER['REQUEST_URI'] ?? '',
                'user_id' => $_SESSION['user_id'] ?? null,
            ]);

            self::response(403, ['error' => 'Invalid CSRF token']);
        }
    }

    public static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function normalizeText(string $value): string
    {
        $value = trim($value);
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        return $value ?? '';
    }

    public static function getString(string $key, string $default = ''): string
    {
        $value = $_REQUEST[$key] ?? $default;

        if (!is_string($value)) {
            return $default;
        }

        return trim($value);
    }

    public static function getSanitizedString(string $key, string $default = ''): string
    {
        return self::normalizeText(self::getString($key, $default));
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $value = $_REQUEST[$key] ?? null;
        $int = filter_var($value, FILTER_VALIDATE_INT);

        return $int === false ? $default : $int;
    }

    public static function getFloat(string $key, float $default = 0.0): float
    {
        $value = $_REQUEST[$key] ?? null;

        if (!is_scalar($value)) {
            return $default;
        }

        $value = str_replace(',', '.', (string) $value);
        $float = filter_var($value, FILTER_VALIDATE_FLOAT);

        return $float === false ? $default : (float) $float;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = $_REQUEST[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public static function getEmail(string $key): ?string
    {
        $value = $_REQUEST[$key] ?? null;

        if (!is_string($value)) {
            return null;
        }

        return filter_var(trim($value), FILTER_VALIDATE_EMAIL) ?: null;
    }

    public static function getUrl(string $key): ?string
    {
        $value = $_REQUEST[$key] ?? null;

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return filter_var($value, FILTER_VALIDATE_URL) ?: null;
    }



    public static function postString(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    public static function postInt(string $key, int $default = 0): int
    {
        $value = $_POST[$key] ?? null;
        $int = filter_var($value, FILTER_VALIDATE_INT);

        return $int === false ? $default : $int;
    }

    public static function sanitizeFilename(string $filename): string
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        $filename = $filename ?? 'file';
        return substr($filename, 0, 255);
    }

    public static function safePathJoin(string $baseDir, string $name): string
    {
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR);
        $name = ltrim(str_replace(["\0", '..'], ['', ''], $name), DIRECTORY_SEPARATOR);

        return $baseDir . DIRECTORY_SEPARATOR . $name;
    }

    public static function redirect(string $url, int $statusCode = 302): never
    {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }

    public static function response(int $statusCode, array $payload = []): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');

        echo json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_THROW_ON_ERROR
        );

        exit;
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function userId(): int|string|null
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role(): ?string
    {
        $role = $_SESSION['role'] ?? null;
        return is_string($role) ? $role : null;
    }

    public static function hasRole(string $role): bool
    {
        return self::role() === $role;
    }

    public static function hasAnyRole(array $roles): bool
    {
        $current = self::role();
        return $current !== null && in_array($current, $roles, true);
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            self::audit('auth_required_redirect', [
                'path' => $_SERVER['REQUEST_URI'] ?? '',
            ]);

            self::redirect('login.php');
        }
    }

    public static function requireRole(string|array $roles): void
    {
        self::requireLogin();

        $allowed = is_array($roles) ? $roles : [$roles];

        if (!self::hasAnyRole($allowed)) {
            self::audit('access_denied', [
                'user_id' => self::userId(),
                'required_roles' => $allowed,
                'path' => $_SERVER['REQUEST_URI'] ?? '',
            ]);

            self::response(403, ['error' => 'Access denied']);
        }
    }

    public static function loginUser(array $user, bool $rememberMe = false): void
    {
        self::regenerateSession(true);

        $_SESSION['user_id'] = $user['id'] ?? null;
        $_SESSION['role'] = $user['role'] ?? 'student';
        $_SESSION['login_time'] = time();
        $_SESSION['_last_activity'] = time();
        $_SESSION['_fp'] = self::fingerprint();
        $_SESSION['_last_regenerated_at'] = time();

        self::audit('login_success', [
            'user_id' => $_SESSION['user_id'],
            'role' => $_SESSION['role'],
        ]);

        if ($rememberMe) {
            self::attachRememberMeCookie((int) ($_SESSION['user_id'] ?? 0));
        }
    }

    public static function logoutUser(): void
    {
        $userId = self::userId();
        self::clearRememberMeCookie();
        self::audit('logout', ['user_id' => $userId]);
        self::destroySession();
    }

    public static function attachRememberMeCookie(int $userId): void
    {
        $pair = self::createRememberTokenPair();

        self::setCookie('remember_selector', $pair['selector'], time() + (86400 * (int) self::$config['remember_me_days']));
        self::setCookie('remember_token', $pair['token'], time() + (86400 * (int) self::$config['remember_me_days']));

        self::audit('remember_me_issued', [
            'user_id' => $userId,
            'selector' => $pair['selector'],
        ]);

        // Persist $pair['selector'] and password_hash($pair['token'], PASSWORD_DEFAULT)
        // in your database associated with the user.
    }

    public static function clearRememberMeCookie(): void
    {
        self::setCookie('remember_selector', '', time() - 3600);
        self::setCookie('remember_token', '', time() - 3600);
    }

    public static function createRememberTokenPair(): array
    {
        return [
            'selector' => bin2hex(random_bytes(8)),
            'token' => bin2hex(random_bytes(32)),
        ];
    }

    public static function passwordPolicyScore(string $password): int
    {
        $score = 0;

        if (strlen($password) >= 12) {
            $score += 2;
        } elseif (strlen($password) >= 8) {
            $score += 1;
        }

        if (preg_match('/[a-z]/', $password)) {
            $score += 1;
        }
        if (preg_match('/[A-Z]/', $password)) {
            $score += 1;
        }
        if (preg_match('/[0-9]/', $password)) {
            $score += 1;
        }
        if (preg_match('/[\W_]/', $password)) {
            $score += 1;
        }

        $common = ['password', 'qwerty', '123456', 'letmein', 'admin'];
        foreach ($common as $bad) {
            if (stripos($password, $bad) !== false) {
                $score -= 2;
            }
        }

        return max(0, $score);
    }

    public static function validatePassword(string $password, int $minLen = self::DEFAULT_PASSWORD_MIN_LEN): bool
    {
        if (strlen($password) < $minLen) {
            return false;
        }

        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        if (!preg_match('/[\W_]/', $password)) {
            return false;
        }

        $score = self::passwordPolicyScore($password);

        return $score >= 4;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function passwordNeedsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    public static function bruteForceKey(string $identifier): string
    {
        return 'bf:' . sha1($identifier);
    }

    public static function rateLimit(string $key, int $limit = self::DEFAULT_RATE_LIMIT_MAX, int $window = self::DEFAULT_RATE_LIMIT_WINDOW): bool
    {
        $store = self::rateStorePath($key);
        $now = time();
        $hits = [];

        if (is_file($store)) {
            $raw = file_get_contents($store);
            $hits = is_string($raw) ? (json_decode($raw, true) ?: []) : [];
        }

        $hits = array_values(array_filter(
            $hits,
            static fn ($timestamp) => is_int($timestamp) && ($now - $timestamp) < $window
        ));

        if (count($hits) >= $limit) {
            return false;
        }

        $hits[] = $now;
        self::writeAtomic($store, json_encode($hits, JSON_THROW_ON_ERROR));

        return true;
    }

    public static function rateLimitOrFail(string $key, int $limit = self::DEFAULT_RATE_LIMIT_MAX, int $window = self::DEFAULT_RATE_LIMIT_WINDOW): void
    {
        if (!self::rateLimit($key, $limit, $window)) {
            self::audit('rate_limited', ['key' => $key]);
            self::response(429, ['error' => 'Too many requests']);
        }
    }

    public static function loginThrottle(string $identifier): bool
    {
        return self::rateLimit(
            self::bruteForceKey('login:' . $identifier),
            self::DEFAULT_LOGIN_LIMIT,
            self::DEFAULT_LOGIN_WINDOW
        );
    }

    public static function markLoginFailure(string $identifier): void
    {
        self::audit('login_failed', ['identifier' => $identifier]);
        self::rateLimit(self::bruteForceKey('login:' . $identifier), self::DEFAULT_LOGIN_LIMIT, self::DEFAULT_LOGIN_WINDOW);
    }

    public static function markLoginSuccess(string $identifier): void
    {
        self::audit('login_success_throttle_reset', ['identifier' => $identifier]);
        self::clearRateLimit(self::bruteForceKey('login:' . $identifier));
    }

    public static function clearRateLimit(string $key): void
    {
        $store = self::rateStorePath($key);
        if (is_file($store)) {
            @unlink($store);
        }
    }

    private static function rateStorePath(string $key): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sec_rl_' . sha1($key) . '.json';
    }

    public static function validateUpload(
        array $file,
        array $allowedMime = ['image/jpeg', 'image/png', 'application/pdf'],
        int $maxSize = self::DEFAULT_UPLOAD_MAX_BYTES
    ): array {
        if (!isset($file['error'], $file['tmp_name'], $file['size'], $file['name'])) {
            return [false, 'Malformed upload data'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [false, 'Upload error'];
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return [false, 'Not an uploaded file'];
        }

        if ((int) $file['size'] <= 0 || (int) $file['size'] > $maxSize) {
            return [false, 'File too large'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';

        if (!in_array($mime, $allowedMime, true)) {
            return [false, 'Invalid file type'];
        }

        $name = self::sanitizeFilename((string) $file['name']);

        return [true, [
            'name' => $name,
            'mime' => $mime,
            'size' => (int) $file['size'],
            'tmp_name' => $file['tmp_name'],
        ]];
    }

    public static function moveUploadedFile(array $file, string $destinationDir, ?string $newName = null): string
    {
        $result = self::validateUpload($file);
        if ($result[0] !== true) {
            self::response(400, ['error' => $result[1]]);
        }

        /** @var array{name:string,mime:string,size:int,tmp_name:string} $data */
        $data = $result[1];

        if (!is_dir($destinationDir) && !mkdir($destinationDir, 0750, true) && !is_dir($destinationDir)) {
            self::response(500, ['error' => 'Cannot create upload directory']);
        }

        $safeName = $newName !== null ? self::sanitizeFilename($newName) : $data['name'];
        $target = rtrim($destinationDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file($data['tmp_name'], $target)) {
            self::response(500, ['error' => 'Upload move failed']);
        }

        self::audit('file_uploaded', [
            'name' => $safeName,
            'mime' => $data['mime'],
            'size' => $data['size'],
        ]);

        return $target;
    }

    public static function safeFileExtension(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return preg_replace('/[^a-z0-9]/', '', $ext) ?? '';
    }

    public static function assertAllowedExtension(string $filename, array $allowed): void
    {
        $ext = self::safeFileExtension($filename);

        if (!in_array($ext, $allowed, true)) {
            self::response(400, ['error' => 'Extension not allowed']);
        }
    }

    public static function pathInside(string $baseDir, string $path): bool
    {
        $base = realpath($baseDir);
        $candidate = realpath($path);

        if ($base === false || $candidate === false) {
            return false;
        }

        return str_starts_with($candidate, $base . DIRECTORY_SEPARATOR) || $candidate === $base;
    }

    public static function safeOpen(string $filePath, string $mode = 'r')
    {
        $handle = @fopen($filePath, $mode);
        if ($handle === false) {
            throw new RuntimeException('Unable to open file');
        }
        return $handle;
    }

    public static function setCookie(string $name, string $value, int $expires = 0, string $path = '/', ?string $domain = null): void
    {
        setcookie($name, $value, [
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain ?? '',
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => (string) self::$config['cookie_samesite'],
        ]);
    }

    public static function audit(string $event, array $context = []): void
    {
        $logFile = self::$config['audit_log_file'] ?? (dirname(__DIR__) . DIRECTORY_SEPARATOR . 'security_audit.log');
        $entry = [
            'ts' => date('c'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'user_id' => $_SESSION['user_id'] ?? null,
            'context' => $context,
        ];

        self::writeAtomic($logFile, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL, true);
    }

    private static function writeAtomic(string $file, string $content, bool $append = false): void
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }

        if ($append) {
            file_put_contents($file, $content, FILE_APPEND | LOCK_EX);
            return;
        }

        $tmp = $file . '.tmp.' . bin2hex(random_bytes(4));
        file_put_contents($tmp, $content, LOCK_EX);
        rename($tmp, $file);
    }

    public static function pdo(string $dsn, string $user, string $password, array $options = []): PDO
    {
        $defaults = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new PDO($dsn, $user, $password, $options + $defaults);
    }



    public static function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            self::response(405, ['error' => 'Method not allowed']);
        }
    }



    public static function isSameOriginRequest(): bool
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        if ($origin !== '') {
            return str_contains($origin, $host);
        }

        if ($referer !== '') {
            return str_contains($referer, $host);
        }

        return false;
    }

    public static function assertSameOrigin(): void
    {
        if (!self::isSameOriginRequest()) {
            self::response(403, ['error' => 'Same-origin policy violation']);
        }
    }



    public static function withTransaction(PDO $pdo, callable $callback): mixed
    {
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }


}



if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken(string $token): bool
    {
        return SecurityEnterprise::verifyCsrf($token);
    }
}

if (!function_exists('sanitize')) {
    function sanitize(string $value): string
    {
        return SecurityEnterprise::normalizeText($value);
    }
}

if (!function_exists('csrfField')) {
    function csrfField(): string
    {
        return SecurityEnterprise::csrfField();
    }
}

if (!function_exists('csrfMetaTag')) {
    function csrfMetaTag(): string
    {
        return SecurityEnterprise::csrfMetaTag();
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): never
    {
        SecurityEnterprise::redirect($url);
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool
    {
        return SecurityEnterprise::isLoggedIn();
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin(): void
    {
        SecurityEnterprise::requireLogin();
    }
}

if (!function_exists('isTeacher')) {
    function isTeacher(): bool
    {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher';
    }
}

if (!function_exists('isStudent')) {
    function isStudent(): bool
    {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student';
    }
}

if (!function_exists('isStudentCreator')) {
    function isStudentCreator(): bool
    {
        return isset($_SESSION['is_student_creator']) && (int)$_SESSION['is_student_creator'] === 1;
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin(): bool
    {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    }
}

if (!function_exists('requireTeacher')) {
    function requireTeacher(): void
    {
        requireLogin();
        if (!isTeacher()) {
            redirect('student_dashboard.php');
        }
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin(): void
    {
        requireLogin();
        if (!isAdmin()) {
            redirect('login.php');
        }
    }
}


