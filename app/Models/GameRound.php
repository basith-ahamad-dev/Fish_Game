<?php

namespace App\Models;

use App\Core\Model;

class GameRound extends Model
{
    protected string $table = 'game_rounds';

    protected array $fillable = [
        'game_id', 'round_number', 'fish_count', 'chest_count', 'answer',
        'status', 'is_correct', 'points', 'served_ms', 'answered_ms',
    ];

    public function create(array $data): int
    {
        return $this->insert($data);
    }

    public function pendingForGame(int $gameId): ?array
    {
        return $this->fetch(
            "SELECT * FROM game_rounds WHERE game_id = ? AND status = 'pending' ORDER BY round_number DESC LIMIT 1",
            [$gameId]
        );
    }

    /** Close any open picture as timed out (used when a game is abandoned). */
    public function closePending(int $gameId, int $nowMs): void
    {
        $this->execute(
            "UPDATE game_rounds SET status = 'timeout', answered_ms = ? WHERE game_id = ? AND status = 'pending'",
            [$nowMs, $gameId]
        );
    }

    public function countForGame(int $gameId): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM game_rounds WHERE game_id = ?', [$gameId]);
    }

    public function forGame(int $gameId): array
    {
        return $this->fetchAll(
            'SELECT round_number, fish_count, chest_count, answer, status, is_correct, points, served_ms, answered_ms
             FROM game_rounds WHERE game_id = ? ORDER BY round_number',
            [$gameId]
        );
    }

    /** Average answer time in seconds for a player's correct answers. */
    public function averageAnswerSeconds(int $userId): ?float
    {
        $value = $this->fetchColumn(
            "SELECT AVG(r.answered_ms - r.served_ms) / 1000 FROM game_rounds r
             JOIN games g ON g.id = r.game_id
             WHERE g.user_id = ? AND r.is_correct = 1",
            [$userId]
        );

        return $value === null || $value === false ? null : (float) $value;
    }
}
