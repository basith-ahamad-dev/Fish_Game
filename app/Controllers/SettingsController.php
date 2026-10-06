<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\AccountService;

class SettingsController extends Controller
{
    private const SECTIONS = ['profile', 'security', 'preferences'];

    public function index(): Response
    {
        $section = (string) $this->request->query('tab', 'profile');

        return $this->view('settings/index', [
            'title'   => 'Settings',
            'user'    => $this->user(),
            'section' => in_array($section, self::SECTIONS, true) ? $section : 'profile',
        ]);
    }

    /** POST /settings — `section` selects which part of the account to update. */
    public function update(): Response
    {
        $section = $this->request->string('section');
        $account = new AccountService();
        $user = $this->user();

        $errors = match ($section) {
            'profile' => $account->updateProfile(
                $user,
                $this->request->only(['username', 'bio']),
                $this->request->file('avatar'),
                $this->request->bool('remove_avatar')
            ),
            'security'    => $account->changePassword($user, $this->request->only(['current_password', 'password', 'password_confirmation'])),
            'preferences' => $account->updatePreferences($user, $this->request->only(['sound_enabled', 'animations_enabled', 'theme'])),
            default       => ['section' => 'Unknown settings section.'],
        };

        $redirect = '/settings?tab=' . (in_array($section, self::SECTIONS, true) ? $section : 'profile');
        if ($errors) {
            throw $this->validationFailed($errors, $redirect);
        }

        $messages = [
            'profile'     => 'Profile updated.',
            'security'    => 'Password changed. Other devices have been signed out.',
            'preferences' => 'Preferences saved.',
        ];
        $this->flash('success', $messages[$section]);

        return $this->redirect($redirect);
    }
}
