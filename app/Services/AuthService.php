<?php

namespace App\Services;

use App\Core\Request;
use App\Core\Session;
use App\Models\LoginAttempt;
use App\Models\RememberToken;
use App\Models\User;

class AuthService
{
    private const SESSION_KEY = 'user_id';
    private const REMEMBER_COOKIE = 'deepcatch_remember';

    private static ?array $user = null;

    private User $users;
    private LoginAttempt $attempts;
    private RememberToken $tokens;

    public function __construct()
    {
        $this->users = new User();
        $this->attempts = new LoginAttempt();
        $this->tokens = new RememberToken();
    }

    /** Resolve the current player from the session, falling back to the remember-me cookie. */
    public static function bootstrap(Request $request): void
    {
        $auth = new self();
        $id = Session::get(self::SESSION_KEY);

        if ($id) {
            $user = $auth->users->find((int) $id);
            if ($user && $user['status'] === 'active') {
                self::$user = $user;
                return;
            }
            Session::forget(self::SESSION_KEY);
        }

        $cookie = $request->cookie(self::REMEMBER_COOKIE);
        if ($cookie) {
            $auth->loginFromRememberCookie($cookie);
        }
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function id(): ?int
    {
        return isset(self::$user['id']) ? (int) self::$user['id'] : null;
    }

    /** Reload the cached player after their row changed (game result, settings, …). */
    public static function refresh(): void
    {
        if (self::$user) {
            self::$user = (new User())->find((int) self::$user['id']);
        }
    }

    /**
     * @return array<string,string> field => message for taken username/email
     */
    public function registrationConflicts(string $username, string $email): array
    {
        $errors = [];
        if ($this->users->usernameTaken($username)) {
            $errors['username'] = 'That username is already taken.';
        }
        if ($this->users->emailTaken($email)) {
            $errors['email'] = 'An account with this email already exists.';
        }

        return $errors;
    }

    public function register(string $username, string $email, string $password): array
    {
        $id = $this->users->create([
            'username' => $username,
            'email'    => mb_strtolower($email),
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $user = $this->users->find($id);
        $this->login($user, false);

        return $user;
    }

    /**
     * @return string|null error message, or null on success
     */
    public function attempt(string $identifier, string $password, bool $remember, string $ip): ?string
    {
        $throttle = config('app.login_throttle');
        $this->attempts->pruneOlderThan($throttle['decay_minutes'] * 4);

        if ($this->attempts->countRecent($ip, $identifier, $throttle['decay_minutes']) >= $throttle['max_attempts']
            || $this->attempts->countRecentByIp($ip, $throttle['decay_minutes']) >= $throttle['ip_max_attempts']) {
            $wait = max(1, (int) ceil($this->attempts->secondsUntilRelease($ip, $identifier, $throttle['decay_minutes']) / 60));
            return "Too many login attempts. Please try again in $wait minute" . ($wait === 1 ? '' : 's') . '.';
        }

        $user = $this->users->findByIdentifier($identifier);

        // Verify against a dummy hash when the user doesn't exist to keep timing uniform.
        $hash = $user['password'] ?? '$2y$10$LZSp2O7NCDNqoauS58qxrebFNXBoqWSaWqiluEKFFOjQWdA/nUlzi';
        $valid = password_verify($password, $hash) && $user !== null;

        if (!$valid) {
            $this->attempts->record($ip, $identifier);
            return 'These credentials do not match our records.';
        }

        if ($user['status'] !== 'active') {
            return 'This account has been disabled. Please contact support.';
        }

        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $this->users->updateById((int) $user['id'], ['password' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        $this->attempts->clear($ip, $identifier);
        $this->login($user, $remember);

        return null;
    }

    public function login(array $user, bool $remember): void
    {
        Session::regenerate();
        Session::set(self::SESSION_KEY, (int) $user['id']);
        Session::forget('_csrf');
        $this->users->touchLogin((int) $user['id']);
        self::$user = $user;

        if ($remember) {
            $this->issueRememberToken((int) $user['id']);
        }
    }

    public function logout(Request $request): void
    {
        $cookie = $request->cookie(self::REMEMBER_COOKIE);
        if ($cookie && str_contains($cookie, ':')) {
            $this->tokens->deleteBySelector(explode(':', $cookie, 2)[0]);
        }
        $this->setRememberCookie('', time() - 3600);

        Session::destroy();
        self::$user = null;
    }

    public function verifyPassword(array $user, string $password): bool
    {
        return password_verify($password, $user['password']);
    }

    private function loginFromRememberCookie(string $cookie): void
    {
        [$selector, $validator] = array_pad(explode(':', $cookie, 2), 2, '');
        $token = $selector !== '' ? $this->tokens->findValid($selector) : null;

        if (!$token || !hash_equals($token['token_hash'], hash('sha256', $validator))) {
            $this->setRememberCookie('', time() - 3600);
            return;
        }

        // Rotate on every use so a stolen cookie only works once.
        $this->tokens->deleteBySelector($selector);
        $user = $this->users->find((int) $token['user_id']);
        if ($user && $user['status'] === 'active') {
            $this->login($user, true);
        }
    }

    private function issueRememberToken(int $userId): void
    {
        $this->tokens->pruneExpired();

        $days = (int) config('app.session.remember_days');
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $expires = time() + $days * 86400;

        $this->tokens->create($userId, $selector, hash('sha256', $validator), date('Y-m-d H:i:s', $expires));
        $this->setRememberCookie("$selector:$validator", $expires);
    }

    private function setRememberCookie(string $value, int $expires): void
    {
        setcookie(self::REMEMBER_COOKIE, $value, [
            'expires'  => $expires,
            'path'     => Request::basePath() ?: '/',
            'secure'   => Session::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
