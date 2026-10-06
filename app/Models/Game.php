<?php

namespace App\Models;

use App\Core\Model;

class Game extends Model
{
    protected string $table = 'games';

    protected array $fillable = [
        'user_id', 'difficulty', 'status', 'result', 'score', 'xp_earned', 'total_rounds', 'rounds_played',
        'correct_answers', 'lives_left', 'fish_caught', 'rank_after', 'game_data', 'played_at',
    ];

    public function create(int $userId, string $difficulty, int $rounds, int $lives): int
    {
        return $this->insert([
            'user_id'      => $userId,
            'difficulty'   => $difficulty,
            'total_rounds' => $rounds,
            'lives_left'   => $lives,
        ]);
    }

    public function findForUser(int $id, int $userId): ?array
    {
        return $this->fetch('SELECT * FROM games WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    /** Row-locks the game for the rest of the current transaction. */
    public function lockForUser(int $id, int $userId): ?array
    {
        return $this->fetch('SELECT * FROM games WHERE id = ? AND user_id = ? FOR UPDATE', [$id, $userId]);
    }

    public function inProgressForUser(int $userId): array
    {
        return $this->fetchAll(
            "SELECT * FROM games WHERE user_id = ? AND status = 'in_progress' ORDER BY id",
            [$userId]
        );
    }

    public function recentForUser(int $userId, int $limit = 5): array
    {
        return $this->fetchAll(
            "SELECT * FROM games WHERE user_id = ? AND status = 'completed'
             ORDER BY played_at DESC, id DESC LIMIT $limit",
            [$userId]
        );
    }

    /**
     * @return array{0: array, 1: int}
     */
    public function paginateForUser(int $userId, ?string $difficulty, ?string $result, int $page, int $perPage): array
    {
        $where = ["user_id = ?", "status = 'completed'"];
        $params = [$userId];

        if ($difficulty) {
            $where[] = 'difficulty = ?';
            $params[] = $difficulty;
        }
        if ($result) {
            $where[] = 'result = ?';
            $params[] = $result;
        }

        $sqlWhere = implode(' AND ', $where);
        $total = (int) $this->fetchColumn("SELECT COUNT(*) FROM games WHERE $sqlWhere", $params);
        $offset = ($page - 1) * $perPage;
        $rows = $this->fetchAll(
            "SELECT * FROM games WHERE $sqlWhere ORDER BY played_at DESC, id DESC LIMIT $perPage OFFSET $offset",
            $params
        );

        return [$rows, $total];
    }

    /**
     * Admin listing across all players.
     *
     * @return array{0: array, 1: int}
     */
    public function adminSearch(string $username, ?string $difficulty, ?string $result, int $page, int $perPage): array
    {
        $where = ["g.status = 'completed'"];
        $params = [];

        if ($username !== '') {
            $where[] = 'u.username LIKE ?';
            $params[] = "%$username%";
        }
        if ($difficulty) {
            $where[] = 'g.difficulty = ?';
            $params[] = $difficulty;
        }
        if ($result) {
            $where[] = 'g.result = ?';
            $params[] = $result;
        }

        $sqlWhere = implode(' AND ', $where);
        $total = (int) $this->fetchColumn(
            "SELECT COUNT(*) FROM games g JOIN users u ON u.id = g.user_id WHERE $sqlWhere",
            $params
        );
        $offset = ($page - 1) * $perPage;
        $rows = $this->fetchAll(
            "SELECT g.*, u.username, u.avatar FROM games g JOIN users u ON u.id = g.user_id
             WHERE $sqlWhere ORDER BY g.played_at DESC, g.id DESC LIMIT $perPage OFFSET $offset",
            $params
        );

        return [$rows, $total];
    }

    public function countWins(int $userId, ?string $difficulty = null): int
    {
        $sql = "SELECT COUNT(*) FROM games WHERE user_id = ? AND status = 'completed' AND result = 'won'";
        $params = [$userId];
        if ($difficulty) {
            $sql .= ' AND difficulty = ?';
            $params[] = $difficulty;
        }

        return (int) $this->fetchColumn($sql, $params);
    }

    public function countPerfect(int $userId): int
    {
        return (int) $this->fetchColumn(
            "SELECT COUNT(*) FROM games WHERE user_id = ? AND status = 'completed'
             AND result = 'won' AND correct_answers = total_rounds",
            [$userId]
        );
    }

    /** Oldest first — used to rebuild a player's aggregates and streaks. */
    public function completedChronological(int $userId): array
    {
        return $this->fetchAll(
            "SELECT score, xp_earned, result, fish_caught FROM games
             WHERE user_id = ? AND status = 'completed' ORDER BY played_at, id",
            [$userId]
        );
    }

    public function recentAll(int $limit): array
    {
        return $this->fetchAll(
            "SELECT g.*, u.username, u.avatar FROM games g JOIN users u ON u.id = g.user_id
             WHERE g.status = 'completed' ORDER BY g.played_at DESC, g.id DESC LIMIT $limit"
        );
    }
}
