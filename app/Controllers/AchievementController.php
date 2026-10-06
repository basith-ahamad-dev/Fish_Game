<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\BadgeService;

class AchievementController extends Controller
{
    public function index(): Response
    {
        $badges = (new BadgeService())->progressFor($this->user());
        $unlocked = count(array_filter($badges, fn ($b) => $b['unlocked']));

        return $this->view('achievements/index', [
            'title'    => 'Achievements',
            'badges'   => $badges,
            'unlocked' => $unlocked,
            'filter'   => in_array($this->request->query('show'), ['unlocked', 'locked'], true) ? $this->request->query('show') : 'all',
        ]);
    }
}
