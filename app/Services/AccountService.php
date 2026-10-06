<?php

namespace App\Services;

use App\Core\Validator;
use App\Models\RememberToken;
use App\Models\User;

/** Account settings: profile, password and preferences. Methods return field errors (empty = saved). */
class AccountService
{
    private User $users;

    public function __construct(private AvatarService $avatars = new AvatarService())
    {
        $this->users = new User();
    }

    public function updateProfile(array $user, array $input, ?array $avatarFile, bool $removeAvatar): array
    {
        $validator = Validator::make($input, [
            'username' => 'required|username',
            'bio'      => 'nullable|max:160',
        ]);
        $errors = $validator->errors();

        if (!isset($errors['username']) && $this->users->usernameTaken($input['username'], (int) $user['id'])) {
            $errors['username'] = 'That username is already taken.';
        }
        if ($errors) {
            return $errors;
        }

        $data = [
            'username' => $input['username'],
            'bio'      => trim((string) ($input['bio'] ?? '')) ?: null,
        ];

        if ($avatarFile) {
            $stored = $this->avatars->store($avatarFile, (int) $user['id']);
            if (isset($stored['error'])) {
                return ['avatar' => $stored['error']];
            }
            $this->avatars->delete($user['avatar']);
            $data['avatar'] = $stored['path'];
        } elseif ($removeAvatar) {
            $this->avatars->delete($user['avatar']);
            $data['avatar'] = null;
        }

        $this->users->updateById((int) $user['id'], $data);

        return [];
    }

    public function changePassword(array $user, array $input): array
    {
        $validator = Validator::make($input, [
            'current_password' => 'required',
            'password'         => 'required|min:8|max:72|strong_password|confirmed',
        ], ['password' => 'New password']);

        if ($validator->fails()) {
            return $validator->errors();
        }
        if (!password_verify($input['current_password'], $user['password'])) {
            return ['current_password' => 'Your current password is incorrect.'];
        }

        $this->users->updateById((int) $user['id'], ['password' => password_hash($input['password'], PASSWORD_DEFAULT)]);
        // Sign out "remember me" sessions on other devices.
        (new RememberToken())->deleteForUser((int) $user['id']);

        return [];
    }

    public function updatePreferences(array $user, array $input): array
    {
        $theme = in_array($input['theme'] ?? '', ['dark', 'light'], true) ? $input['theme'] : 'dark';

        $this->users->updateById((int) $user['id'], [
            'sound_enabled'      => !empty($input['sound_enabled']) ? 1 : 0,
            'animations_enabled' => !empty($input['animations_enabled']) ? 1 : 0,
            'theme'              => $theme,
        ]);

        return [];
    }
}
