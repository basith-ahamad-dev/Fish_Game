<?php

namespace App\Services;

class XPService
{
    public function __construct(private LevelService $levels = new LevelService())
    {
    }

    /**
     * XP for one game. Abandoned games only keep XP for correct answers.
     */
    public function calculate(string $difficulty, int $correct, string $result, bool $perfect): int
    {
        $rules = config('game.xp');
        $multiplier = (float) difficulty_config($difficulty)['xp_mult'];

        $xp = $correct * $rules['per_correct'];
        if ($result !== 'abandoned') {
            $xp += $rules['completion'];
        }
        if ($result === 'won') {
            $xp += $rules['win_bonus'];
        }
        if ($perfect) {
            $xp += $rules['perfect_bonus'];
        }

        return (int) round($xp * $multiplier);
    }

    /**
     * @return array{xp:int, level_before:int, level_after:int, leveled_up:bool}
     */
    public function award(int $currentXp, int $earned): array
    {
        $before = $this->levels->getLevelFromXP($currentXp);
        $total = $currentXp + $earned;
        $after = $this->levels->getLevelFromXP($total);

        return [
            'xp'           => $total,
            'level_before' => $before,
            'level_after'  => $after,
            'leveled_up'   => $after > $before,
        ];
    }
}
