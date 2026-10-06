<?php

namespace App\Models;

use App\Core\Model;

class Badge extends Model
{
    protected string $table = 'badges';

    protected array $fillable = ['name', 'description', 'icon', 'requirement_type', 'requirement_value', 'sort_order'];

    public function all(): array
    {
        return $this->fetchAll('SELECT * FROM badges ORDER BY sort_order, id');
    }

    public function nameTaken(string $name, ?int $exceptId = null): bool
    {
        return (bool) $this->fetchColumn(
            'SELECT COUNT(*) FROM badges WHERE name = ? AND id <> ?',
            [$name, $exceptId ?? 0]
        );
    }

    /** Badges with the number of players who unlocked each. */
    public function allWithUnlockCounts(): array
    {
        return $this->fetchAll(
            'SELECT b.*, COUNT(ub.id) AS unlocked_count
             FROM badges b LEFT JOIN user_badges ub ON ub.badge_id = b.id
             GROUP BY b.id ORDER BY b.sort_order, b.id'
        );
    }
}
