<?php

namespace App\Models;

use App\Core\Model;

/** Aggregate queries over completed games, for players and for the platform. */
class GameStatistic extends Model
{
    protected string $table = 'games';

    public function platformTotals(): array
    {
        return $this->fetch(
            "SELECT
                (SELECT COUNT(*) FROM users WHERE status = 'active') AS players,
                (SELECT COUNT(*) FROM games WHERE status = 'completed') AS games,
                (SELECT COALESCE(SUM(fish_caught), 0) FROM games WHERE status = 'completed') AS fish,
                (SELECT COALESCE(MAX(score), 0) FROM games WHERE status = 'completed') AS high_score,
                (SELECT COALESCE(SUM(score), 0) FROM games WHERE status = 'completed') AS total_score"
        ) ?? [];
    }

    public function activePlayers(int $days): int
    {
        return (int) $this->fetchColumn(
            "SELECT COUNT(DISTINCT user_id) FROM games WHERE status = 'completed' AND played_at >= ?",
            [date('Y-m-d H:i:s', strtotime("-$days days"))]
        );
    }

    /** @return array<string,int> difficulty => games played */
    public function gamesPerDifficulty(): array
    {
        $rows = $this->fetchAll(
            "SELECT difficulty, COUNT(*) AS games FROM games WHERE status = 'completed' GROUP BY difficulty"
        );

        return array_map('intval', array_column($rows, 'games', 'difficulty'));
    }

    /** Games per day for the last $days days, platform-wide or for one player. */
    public function gamesPerDay(int $days, ?int $userId = null): array
    {
        $sql = "SELECT DATE(played_at) AS day, COUNT(*) AS games, COALESCE(SUM(score), 0) AS score
                FROM games WHERE status = 'completed' AND played_at >= ?";
        $params = [date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'))];
        if ($userId) {
            $sql .= ' AND user_id = ?';
            $params[] = $userId;
        }

        return $this->fetchAll($sql . ' GROUP BY DATE(played_at) ORDER BY day', $params);
    }

    /** Per-difficulty performance for one player. */
    public function difficultyBreakdown(int $userId): array
    {
        return $this->fetchAll(
            "SELECT difficulty,
                    COUNT(*) AS games,
                    SUM(result = 'won') AS wins,
                    COALESCE(ROUND(AVG(score)), 0) AS avg_score,
                    COALESCE(MAX(score), 0) AS best_score,
                    COALESCE(SUM(correct_answers), 0) AS correct,
                    COALESCE(SUM(rounds_played), 0) AS rounds
             FROM games WHERE user_id = ? AND status = 'completed'
             GROUP BY difficulty",
            [$userId]
        );
    }

    public function playerTotals(int $userId): array
    {
        return $this->fetch(
            "SELECT COUNT(*) AS games,
                    COALESCE(ROUND(AVG(score)), 0) AS avg_score,
                    COALESCE(SUM(correct_answers), 0) AS correct,
                    COALESCE(SUM(rounds_played), 0) AS rounds,
                    SUM(result = 'won') AS wins
             FROM games WHERE user_id = ? AND status = 'completed'",
            [$userId]
        ) ?? [];
    }

    /** Last $limit completed games, oldest first, for the score progression chart. */
    public function scoreSeries(int $userId, int $limit = 30): array
    {
        $rows = $this->fetchAll(
            "SELECT id, score, difficulty, result, played_at FROM games
             WHERE user_id = ? AND status = 'completed'
             ORDER BY played_at DESC, id DESC LIMIT $limit",
            [$userId]
        );

        return array_reverse($rows);
    }
}
