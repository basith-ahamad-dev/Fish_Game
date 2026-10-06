<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Game;
use App\Models\UserBadge;

class BadgeService
{
    /** Requirement types an admin can choose from, with how progress is measured. */
    public const REQUIREMENTS = [
        'games_played'  => 'Games completed',
        'best_score'    => 'Best single-game score',
        'win_streak'    => 'Consecutive wins (best streak)',
        'global_rank'   => 'Global rank (top N)',
        'level'         => 'Player level',
        'hard_wins'     => 'Hard games won',
        'perfect_games' => 'Perfect games',
        'fish_caught'   => 'Fish caught',
        'total_score'   => 'Total score',
    ];

    public const ICONS = ['hook', 'fish', 'target', 'flame', 'crown', 'star', 'bolt', 'wave', 'anchor', 'gem', 'trophy', 'medal', 'treasure', 'heart'];

    private Badge $badges;
    private UserBadge $userBadges;
    private Game $games;
    private LeaderboardService $leaderboard;

    public function __construct()
    {
        $this->badges = new Badge();
        $this->userBadges = new UserBadge();
        $this->games = new Game();
        $this->leaderboard = new LeaderboardService();
    }

    /**
     * Unlock every badge the player now qualifies for.
     *
     * @return array list of newly unlocked badges
     */
    public function evaluate(array $user, ?int $globalRank = null): array
    {
        $owned = $this->userBadges->unlockedMap((int) $user['id']);
        $metrics = new BadgeMetrics($user, $this->games, $this->leaderboard, $globalRank);
        $unlocked = [];

        foreach ($this->badges->all() as $badge) {
            if (isset($owned[$badge['id']])) {
                continue;
            }
            if ($this->isMet($badge, $metrics) && $this->userBadges->award((int) $user['id'], (int) $badge['id'])) {
                $unlocked[] = [
                    'id'          => (int) $badge['id'],
                    'name'        => $badge['name'],
                    'description' => $badge['description'],
                    'icon'        => $badge['icon'],
                ];
            }
        }

        return $unlocked;
    }

    /**
     * Every badge with lock state and progress, for the achievements page.
     */
    public function progressFor(array $user): array
    {
        $owned = $this->userBadges->unlockedMap((int) $user['id']);
        $metrics = new BadgeMetrics($user, $this->games, $this->leaderboard);
        $list = [];

        foreach ($this->badges->all() as $badge) {
            $target = (int) $badge['requirement_value'];
            $current = $metrics->value($badge['requirement_type']);
            $unlocked = isset($owned[$badge['id']]);

            if ($badge['requirement_type'] === 'global_rank') {
                $percent = $current ? min(100, $target / $current * 100) : 0;
                $label = $current ? "Rank #$current · goal top $target" : "Unranked · goal top $target";
            } else {
                $percent = $target > 0 ? min(100, $current / $target * 100) : 100;
                $label = number(min($current, $target)) . ' / ' . number($target);
            }

            $list[] = $badge + [
                'unlocked'       => $unlocked,
                'unlocked_at'    => $owned[$badge['id']] ?? null,
                'progress'       => $unlocked ? 100 : round($percent),
                'progress_label' => $unlocked ? 'Unlocked' : $label,
            ];
        }

        return $list;
    }

    /**
     * @return array<string,string> field => error
     */
    public function validate(array $data, ?int $id = null): array
    {
        $errors = [];
        if (!isset(self::REQUIREMENTS[$data['requirement_type'] ?? ''])) {
            $errors['requirement_type'] = 'Choose a valid requirement.';
        }
        if (!in_array($data['icon'] ?? '', self::ICONS, true)) {
            $errors['icon'] = 'Choose a valid icon.';
        }
        if ($this->badges->nameTaken((string) ($data['name'] ?? ''), $id)) {
            $errors['name'] = 'A badge with this name already exists.';
        }

        return $errors;
    }

    public function save(array $data, ?int $id = null): int
    {
        $row = [
            'name'              => $data['name'],
            'description'       => $data['description'],
            'icon'              => $data['icon'],
            'requirement_type'  => $data['requirement_type'],
            'requirement_value' => (int) $data['requirement_value'],
            'sort_order'        => (int) ($data['sort_order'] ?? 0),
        ];

        if ($id) {
            $this->badges->updateById($id, $row);
            return $id;
        }

        return $this->badges->insert($row);
    }

    private function isMet(array $badge, BadgeMetrics $metrics): bool
    {
        $value = $metrics->value($badge['requirement_type']);
        $target = (int) $badge['requirement_value'];

        return $badge['requirement_type'] === 'global_rank'
            ? $value > 0 && $value <= $target
            : $value >= $target;
    }
}

/**
 * Lazily computed player metrics so each query runs at most once per evaluation.
 *
 * @internal
 */
final class BadgeMetrics
{
    private array $cache = [];

    public function __construct(
        private array $user,
        private Game $games,
        private LeaderboardService $leaderboard,
        ?int $globalRank = null,
    ) {
        if ($globalRank !== null) {
            $this->cache['global_rank'] = $globalRank;
        }
    }

    public function value(string $type): int
    {
        return $this->cache[$type] ??= match ($type) {
            'games_played'  => (int) $this->user['games_played'],
            'best_score'    => (int) $this->user['best_score'],
            'win_streak'    => (int) $this->user['best_streak'],
            'level'         => (int) $this->user['level'],
            'fish_caught'   => (int) $this->user['fish_caught'],
            'total_score'   => (int) $this->user['total_score'],
            'hard_wins'     => $this->games->countWins((int) $this->user['id'], 'hard'),
            'perfect_games' => $this->games->countPerfect((int) $this->user['id']),
            'global_rank'   => $this->leaderboard->globalRank($this->user) ?? 0,
            default         => 0,
        };
    }
}
