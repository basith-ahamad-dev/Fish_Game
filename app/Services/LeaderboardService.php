<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\User;

class LeaderboardService
{
    public const TABS = [
        'global'  => 'Global',
        'weekly'  => 'Weekly',
        'monthly' => 'Monthly',
        'easy'    => 'Easy',
        'medium'  => 'Medium',
        'hard'    => 'Hard',
    ];

    private Leaderboard $boards;
    private User $users;

    public function __construct()
    {
        $this->boards = new Leaderboard();
        $this->users = new User();
    }

    public function normaliseTab(?string $tab): string
    {
        return isset(self::TABS[$tab]) ? $tab : 'global';
    }

    /**
     * @return array{tab:string, label:string, note:string, rows:array, me:?array}
     *   rows carry a shared competition rank (1, 2, 2, 4); "me" is the viewer's row when outside the list.
     */
    public function board(string $tab, ?int $viewerId = null, int $limit = 50): array
    {
        $tab = $this->normaliseTab($tab);
        [$since, $difficulty, $note] = $this->window($tab);

        $rows = $tab === 'global'
            ? $this->boards->global($limit)
            : $this->boards->fromGames($since, $difficulty, $limit);

        $rows = $this->rank($rows, $viewerId);

        $me = null;
        if ($viewerId && !in_array($viewerId, array_column($rows, 'id'), true)) {
            $me = $this->viewerRow($tab, $viewerId, $since, $difficulty);
        }

        return ['tab' => $tab, 'label' => self::TABS[$tab], 'note' => $note, 'rows' => $rows, 'me' => $me];
    }

    /** Global rank by total score, or null when the player has not completed a game. */
    public function globalRank(array $user): ?int
    {
        if ((int) $user['games_played'] === 0 || $user['status'] !== 'active') {
            return null;
        }

        return $this->users->countAboveScore((int) $user['total_score']) + 1;
    }

    /** Rebuild a player's denormalised totals, level and streaks from their game history. */
    public function recalculateUser(int $userId): void
    {
        $games = (new Game())->completedChronological($userId);
        $levels = new LevelService();

        $totals = ['xp' => 0, 'total_score' => 0, 'games_played' => 0, 'games_won' => 0,
                   'best_score' => 0, 'fish_caught' => 0, 'current_streak' => 0, 'best_streak' => 0];

        foreach ($games as $game) {
            $totals['xp'] += (int) $game['xp_earned'];
            $totals['total_score'] += (int) $game['score'];
            $totals['games_played']++;
            $totals['best_score'] = max($totals['best_score'], (int) $game['score']);
            $totals['fish_caught'] += (int) $game['fish_caught'];

            if ($game['result'] === 'won') {
                $totals['games_won']++;
                $totals['current_streak']++;
                $totals['best_streak'] = max($totals['best_streak'], $totals['current_streak']);
            } else {
                $totals['current_streak'] = 0;
            }
        }

        $totals['level'] = $levels->getLevelFromXP($totals['xp']);
        $this->users->updateById($userId, $totals);
    }

    public function recalculateAll(): int
    {
        $ids = $this->users->allIds();
        Database::transaction(function () use ($ids) {
            foreach ($ids as $id) {
                $this->recalculateUser((int) $id);
            }
        });

        return count($ids);
    }

    /** @return array{0: ?string, 1: ?string, 2: string} since, difficulty, description */
    private function window(string $tab): array
    {
        return match ($tab) {
            'weekly'  => [date('Y-m-d 00:00:00', strtotime('monday this week')), null, 'Resets every Monday'],
            'monthly' => [date('Y-m-01 00:00:00'), null, 'Resets on the 1st of each month'],
            'easy', 'medium', 'hard' => [null, $tab, 'All-time score on ' . self::TABS[$tab]],
            default   => [null, null, 'All-time total score'],
        };
    }

    private function rank(array $rows, ?int $viewerId): array
    {
        $rank = 0;
        $previous = null;

        foreach ($rows as $i => &$row) {
            $row['score'] = (int) $row['score'];
            if ($row['score'] !== $previous) {
                $rank = $i + 1;
                $previous = $row['score'];
            }
            $row['rank'] = $rank;
            $row['is_me'] = $viewerId !== null && (int) $row['id'] === $viewerId;
        }

        return $rows;
    }

    private function viewerRow(string $tab, int $viewerId, ?string $since, ?string $difficulty): ?array
    {
        if ($tab === 'global') {
            $row = $this->boards->globalPlayer($viewerId);
            $above = $row ? $this->users->countAboveScore((int) $row['score']) : 0;
        } else {
            $row = $this->boards->playerFromGames($viewerId, $since, $difficulty);
            $above = $row ? $this->boards->countAboveFromGames((int) $row['score'], $since, $difficulty) : 0;
        }

        if (!$row) {
            return null;
        }

        $row['score'] = (int) $row['score'];
        $row['rank'] = $above + 1;
        $row['is_me'] = true;

        return $row;
    }
}
