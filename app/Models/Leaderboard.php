<?php

namespace App\Models;

use App\Core\Model;

/**
 * Read-only ranking queries. Ordering is score DESC; ties are resolved into shared
 * ranks by LeaderboardService.
 */
class Leaderboard extends Model
{
    protected string $table = 'users';

    private const BADGE_COUNT = '(SELECT COUNT(*) FROM user_badges ub WHERE ub.user_id = u.id)';

    /** All-time board, from the denormalised totals on users. */
    public function global(int $limit): array
    {
        return $this->fetchAll(
            "SELECT u.id, u.username, u.avatar, u.level, u.total_score AS score, u.games_played AS games, "
            . self::BADGE_COUNT . " AS badges
             FROM users u
             WHERE u.status = 'active' AND u.games_played > 0
             ORDER BY u.total_score DESC, u.username ASC
             LIMIT $limit"
        );
    }

    /** Sum of scores from games completed since $since, optionally for one difficulty. */
    public function fromGames(?string $since, ?string $difficulty, int $limit): array
    {
        [$where, $params] = $this->gameFilters($since, $difficulty);

        return $this->fetchAll(
            "SELECT u.id, u.username, u.avatar, u.level, SUM(g.score) AS score, COUNT(g.id) AS games, "
            . self::BADGE_COUNT . " AS badges
             FROM games g JOIN users u ON u.id = g.user_id
             WHERE $where
             GROUP BY u.id, u.username, u.avatar, u.level
             ORDER BY score DESC, u.username ASC
             LIMIT $limit",
            $params
        );
    }

    /** One player's score and games for the same window, used when they sit outside the visible top. */
    public function playerFromGames(int $userId, ?string $since, ?string $difficulty): ?array
    {
        [$where, $params] = $this->gameFilters($since, $difficulty);
        $params[] = $userId;

        return $this->fetch(
            "SELECT u.id, u.username, u.avatar, u.level, SUM(g.score) AS score, COUNT(g.id) AS games, "
            . self::BADGE_COUNT . " AS badges
             FROM games g JOIN users u ON u.id = g.user_id
             WHERE $where AND u.id = ?
             GROUP BY u.id, u.username, u.avatar, u.level",
            $params
        );
    }

    public function countAboveFromGames(int $score, ?string $since, ?string $difficulty): int
    {
        [$where, $params] = $this->gameFilters($since, $difficulty);
        $params[] = $score;

        return (int) $this->fetchColumn(
            "SELECT COUNT(*) FROM (
                SELECT g.user_id FROM games g JOIN users u ON u.id = g.user_id
                WHERE $where GROUP BY g.user_id HAVING SUM(g.score) > ?
             ) ranked",
            $params
        );
    }

    public function globalPlayer(int $userId): ?array
    {
        return $this->fetch(
            "SELECT u.id, u.username, u.avatar, u.level, u.total_score AS score, u.games_played AS games, "
            . self::BADGE_COUNT . " AS badges
             FROM users u WHERE u.id = ? AND u.status = 'active' AND u.games_played > 0",
            [$userId]
        );
    }

    private function gameFilters(?string $since, ?string $difficulty): array
    {
        $where = ["g.status = 'completed'", "u.status = 'active'"];
        $params = [];
        if ($since) {
            $where[] = 'g.played_at >= ?';
            $params[] = $since;
        }
        if ($difficulty) {
            $where[] = 'g.difficulty = ?';
            $params[] = $difficulty;
        }

        return [implode(' AND ', $where), $params];
    }
}
