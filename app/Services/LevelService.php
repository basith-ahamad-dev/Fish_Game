<?php

namespace App\Services;

/**
 * Cumulative XP needed to complete level L is 25·L·(L+3):
 *   Level 1 → 100, Level 2 → 250, Level 3 → 450, Level 4 → 700, …
 * Each level asks for 50 XP more than the one before.
 */
class LevelService
{
    private const FACTOR = 25;

    /** Total XP at which $level is completed (i.e. the player reaches $level + 1). */
    public function getXPRequiredForLevel(int $level): int
    {
        return $level <= 0 ? 0 : self::FACTOR * $level * ($level + 3);
    }

    public function getLevelFromXP(int $xp): int
    {
        $xp = max(0, $xp);
        // Solve 25·L·(L+3) <= xp for the number of completed levels, then guard against float rounding.
        $completed = (int) floor((-3 + sqrt(9 + 4 * $xp / self::FACTOR)) / 2);
        while ($this->getXPRequiredForLevel($completed + 1) <= $xp) {
            $completed++;
        }
        while ($completed > 0 && $this->getXPRequiredForLevel($completed) > $xp) {
            $completed--;
        }

        return $completed + 1;
    }

    public function getXPRemaining(int $xp): int
    {
        return $this->getXPRequiredForLevel($this->getLevelFromXP($xp)) - $xp;
    }

    /**
     * @return array{level:int, xp:int, level_start:int, level_end:int, current:int, needed:int, remaining:int, percent:float}
     */
    public function getXPProgress(int $xp): array
    {
        $level = $this->getLevelFromXP($xp);
        $start = $this->getXPRequiredForLevel($level - 1);
        $end = $this->getXPRequiredForLevel($level);
        $needed = $end - $start;
        $current = $xp - $start;

        return [
            'level'       => $level,
            'xp'          => $xp,
            'level_start' => $start,
            'level_end'   => $end,
            'current'     => $current,
            'needed'      => $needed,
            'remaining'   => $end - $xp,
            'percent'     => $needed > 0 ? round($current / $needed * 100, 1) : 0.0,
        ];
    }

    /** A short title shown next to the level number. */
    public function titleFor(int $level): string
    {
        return match (true) {
            $level >= 30 => 'Legend of the Deep',
            $level >= 20 => 'Ocean Master',
            $level >= 12 => 'Elite Angler',
            $level >= 7  => 'Seasoned Angler',
            $level >= 3  => 'Angler',
            default      => 'Rookie',
        };
    }
}
