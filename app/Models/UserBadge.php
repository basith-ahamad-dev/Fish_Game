<?php

namespace App\Models;

use App\Core\Model;

class UserBadge extends Model
{
    protected string $table = 'user_badges';

    /** Returns false when the player already owns the badge (unique key prevents duplicates). */
    public function award(int $userId, int $badgeId): bool
    {
        return $this->execute(
            'INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?, ?)',
            [$userId, $badgeId]
        ) === 1;
    }

    /** @return array<int,string> badge_id => unlocked_at */
    public function unlockedMap(int $userId): array
    {
        $rows = $this->fetchAll('SELECT badge_id, unlocked_at FROM user_badges WHERE user_id = ?', [$userId]);

        return array_column($rows, 'unlocked_at', 'badge_id');
    }

    public function recentForUser(int $userId, int $limit = 4): array
    {
        return $this->fetchAll(
            "SELECT b.*, ub.unlocked_at FROM user_badges ub JOIN badges b ON b.id = ub.badge_id
             WHERE ub.user_id = ? ORDER BY ub.unlocked_at DESC, ub.id DESC LIMIT $limit",
            [$userId]
        );
    }

    public function countForUser(int $userId): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM user_badges WHERE user_id = ?', [$userId]);
    }
}
