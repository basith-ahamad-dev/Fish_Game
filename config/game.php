<?php

/*
 * The external Fish API has no notion of difficulty, scoring or sessions — it only returns
 * a random picture and its solution. Everything below is the progression layer that runs
 * on our server on top of it.
 */
return [
    'difficulties' => [
        'easy' => [
            'label'      => 'Easy',
            'stars'      => 1,
            'rounds'     => 5,
            'time_limit' => 30,   // seconds per picture
            'lives'      => 3,
            'score_mult' => 1.0,
            'xp_mult'    => 1.0,
        ],
        'medium' => [
            'label'      => 'Medium',
            'stars'      => 2,
            'rounds'     => 8,
            'time_limit' => 20,
            'lives'      => 2,
            'score_mult' => 1.5,
            'xp_mult'    => 1.5,
        ],
        'hard' => [
            'label'      => 'Hard',
            'stars'      => 3,
            'rounds'     => 10,
            'time_limit' => 12,
            'lives'      => 1,
            'score_mult' => 2.0,
            'xp_mult'    => 2.0,
        ],
    ],

    'scoring' => [
        'base_points'     => 100, // per correct count
        'max_speed_bonus' => 50,  // scaled by the share of time left
        'perfect_bonus'   => 200, // every picture correct
        'grace_ms'        => 2000, // network allowance on the server-side timer
    ],

    'xp' => [
        'per_correct'   => 10,
        'completion'    => 20,
        'win_bonus'     => 30,
        'perfect_bonus' => 25,
    ],
];
