<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'username', 'email', 'password', 'avatar', 'bio', 'role', 'status',
        'level', 'xp', 'total_score', 'games_played', 'games_won', 'best_score', 'fish_caught',
        'current_streak', 'best_streak', 'sound_enabled', 'animations_enabled', 'theme', 'last_login_at',
    ];

    public function findByEmail(string $email): ?array
    {
        return $this->fetch('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->fetch('SELECT * FROM users WHERE username = ?', [$username]);
    }

    /** Login accepts either a username or an email address. */
    public function findByIdentifier(string $identifier): ?array
    {
        return str_contains($identifier, '@') ? $this->findByEmail($identifier) : $this->findByUsername($identifier);
    }

    public function create(array $data): int
    {
        return $this->insert($data);
    }

    public function emailTaken(string $email, ?int $exceptId = null): bool
    {
        return (bool) $this->fetchColumn(
            'SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?',
            [$email, $exceptId ?? 0]
        );
    }

    public function usernameTaken(string $username, ?int $exceptId = null): bool
    {
        return (bool) $this->fetchColumn(
            'SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?',
            [$username, $exceptId ?? 0]
        );
    }

    /** Row-locks the player for the rest of the current transaction. */
    public function lockById(int $id): ?array
    {
        return $this->fetch('SELECT * FROM users WHERE id = ? FOR UPDATE', [$id]);
    }

    public function touchLogin(int $id): void
    {
        $this->execute('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    /** Number of ranked players with a strictly higher total score (for competition ranking). */
    public function countAboveScore(int $score): int
    {
        return (int) $this->fetchColumn(
            "SELECT COUNT(*) FROM users WHERE status = 'active' AND games_played > 0 AND total_score > ?",
            [$score]
        );
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM users WHERE status = ?', [$status]);
    }

    /**
     * Admin listing with search and filters.
     *
     * @return array{0: array, 1: int} rows, total
     */
    public function search(string $term, ?string $status, ?string $role, int $page, int $perPage): array
    {
        $where = ['1 = 1'];
        $params = [];

        if ($term !== '') {
            $where[] = '(username LIKE ? OR email LIKE ?)';
            $params[] = "%$term%";
            $params[] = "%$term%";
        }
        if ($status) {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        if ($role) {
            $where[] = 'role = ?';
            $params[] = $role;
        }

        $sqlWhere = implode(' AND ', $where);
        $total = (int) $this->fetchColumn("SELECT COUNT(*) FROM users WHERE $sqlWhere", $params);
        $offset = ($page - 1) * $perPage;

        $rows = $this->fetchAll(
            "SELECT id, username, email, avatar, role, status, level, xp, total_score, games_played,
                    best_score, created_at, last_login_at
             FROM users WHERE $sqlWhere
             ORDER BY created_at DESC, id DESC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        return [$rows, $total];
    }

    public function allIds(): array
    {
        return array_column($this->fetchAll('SELECT id FROM users'), 'id');
    }

    public function newestPlayers(int $limit): array
    {
        return $this->fetchAll(
            "SELECT id, username, avatar, level, created_at FROM users ORDER BY created_at DESC, id DESC LIMIT $limit"
        );
    }
}
