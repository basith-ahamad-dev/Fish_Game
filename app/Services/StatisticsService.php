<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Game;
use App\Models\GameRound;
use App\Models\GameStatistic;
use App\Models\User;
use App\Models\UserBadge;

class StatisticsService
{
    private GameStatistic $stats;

    public function __construct()
    {
        $this->stats = new GameStatistic();
    }

    public function winRate(array $user): int
    {
        $played = (int) $user['games_played'];

        return $played > 0 ? (int) round((int) $user['games_won'] / $played * 100) : 0;
    }

    /** Everything the player dashboard shows. */
    public function dashboard(array $user): array
    {
        $userId = (int) $user['id'];
        $userBadges = new UserBadge();

        return [
            'progress'      => (new LevelService())->getXPProgress((int) $user['xp']),
            'title'         => (new LevelService())->titleFor((int) $user['level']),
            'rank'          => (new LeaderboardService())->globalRank($user),
            'win_rate'      => $this->winRate($user),
            'recent_games'  => (new Game())->recentForUser($userId, 5),
            'recent_badges' => $userBadges->recentForUser($userId, 4),
            'badge_count'   => $userBadges->countForUser($userId),
            'badge_total'   => (new Badge())->count(),
            'activity'      => $this->activity($userId, 7),
        ];
    }

    /** Full statistics page data. */
    public function forUser(array $user): array
    {
        $userId = (int) $user['id'];
        $totals = $this->stats->playerTotals($userId);
        $rounds = (int) ($totals['rounds'] ?? 0);
        $avgTime = (new GameRound())->averageAnswerSeconds($userId);

        $byDifficulty = [];
        foreach ($this->stats->difficultyBreakdown($userId) as $row) {
            $byDifficulty[$row['difficulty']] = $row;
        }

        $difficulties = [];
        foreach (difficulty_config() as $key => $config) {
            $row = $byDifficulty[$key] ?? [];
            $games = (int) ($row['games'] ?? 0);
            $played = (int) ($row['rounds'] ?? 0);
            $difficulties[$key] = [
                'label'      => $config['label'],
                'stars'      => $config['stars'],
                'games'      => $games,
                'wins'       => (int) ($row['wins'] ?? 0),
                'win_rate'   => $games ? (int) round((int) $row['wins'] / $games * 100) : 0,
                'avg_score'  => (int) ($row['avg_score'] ?? 0),
                'best_score' => (int) ($row['best_score'] ?? 0),
                'accuracy'   => $played ? (int) round((int) $row['correct'] / $played * 100) : 0,
            ];
        }

        return [
            'totals' => [
                'games'          => (int) $user['games_played'],
                'wins'           => (int) $user['games_won'],
                'win_rate'       => $this->winRate($user),
                'avg_score'      => (int) ($totals['avg_score'] ?? 0),
                'best_score'     => (int) $user['best_score'],
                'total_score'    => (int) $user['total_score'],
                'total_xp'       => (int) $user['xp'],
                'fish'           => (int) $user['fish_caught'],
                'current_streak' => (int) $user['current_streak'],
                'best_streak'    => (int) $user['best_streak'],
                'accuracy'       => $rounds ? (int) round((int) $totals['correct'] / $rounds * 100) : 0,
                'avg_time'       => $avgTime !== null ? round($avgTime, 1) : null,
            ],
            'difficulties' => $difficulties,
            'series'       => array_map(fn ($g) => [
                'id'         => (int) $g['id'],
                'score'      => (int) $g['score'],
                'difficulty' => $g['difficulty'],
                'result'     => $g['result'],
                'date'       => date('M j', strtotime($g['played_at'])),
            ], $this->stats->scoreSeries($userId, 30)),
            'activity' => $this->activity($userId, 14),
        ];
    }

    /** Landing-page counters. */
    public function platform(): array
    {
        $t = $this->stats->platformTotals();

        return [
            'players'    => (int) ($t['players'] ?? 0),
            'games'      => (int) ($t['games'] ?? 0),
            'fish'       => (int) ($t['fish'] ?? 0),
            'high_score' => (int) ($t['high_score'] ?? 0),
        ];
    }

    /** Admin dashboard metrics. */
    public function admin(): array
    {
        $totals = $this->stats->platformTotals();
        $perDifficulty = $this->stats->gamesPerDifficulty();
        arsort($perDifficulty);
        $popular = array_key_first($perDifficulty);

        return [
            'players'           => (int) ($totals['players'] ?? 0),
            'disabled'          => (new User())->countByStatus('disabled'),
            'games'             => (int) ($totals['games'] ?? 0),
            'active_players'    => $this->stats->activePlayers(7),
            'total_score'       => (int) ($totals['total_score'] ?? 0),
            'fish'              => (int) ($totals['fish'] ?? 0),
            'popular'           => $popular ? difficulty_config($popular)['label'] : '—',
            'per_difficulty'    => $perDifficulty,
            'activity'          => $this->activity(null, 14),
        ];
    }

    /** Games per day with zero-filled gaps. */
    private function activity(?int $userId, int $days): array
    {
        $rows = array_column($this->stats->gamesPerDay($days, $userId), null, 'day');
        $out = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $out[] = [
                'day'   => $day,
                'label' => date('D', strtotime($day)),
                'date'  => date('M j', strtotime($day)),
                'games' => (int) ($rows[$day]['games'] ?? 0),
                'score' => (int) ($rows[$day]['score'] ?? 0),
            ];
        }

        return $out;
    }
}
