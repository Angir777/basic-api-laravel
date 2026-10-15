<?php

namespace App\Services\Account;

use App\Enums\EHistoryActionType;
use App\Enums\EUserSettingName;
use App\Events\UserChanged;
use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class AccountService
{
    public function changePassword(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $oldData = User::with('settings')->where('id', '=', $user->id)->first();
            $oldData->history_comment = null;

            $res = $user->update([
                'password' => Hash::make($data['newPassword']),
            ]);

            if (! $res) {
                throw new Exception('CANT_CHANGE_PASSWORD');
            }

            // Entry in the change history.
            $newData = User::with('settings')->where('id', '=', $user->id)->first();
            $newData->history_comment = 'COMMENT_CHANGE_PASSWORD';
            event(
                new UserChanged(
                    $oldData,
                    $newData,
                    EHistoryActionType::UPDATE
                )
            );

            return $user;
        });
    }

    public function updateDetailsUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $oldData = User::with('settings')->where('id', '=', $user->id)->first();

            $user->update([
                'first_name' => $data['firstName'],
                'last_name' => $data['lastName'],
            ]);

            $removeAvatar = ($data['removeAvatar'] ?? 'false') === 'true';
            if ($removeAvatar) {
                $this->deleteUserAvatar($user);
            } elseif (($data['avatar'] ?? null) instanceof UploadedFile) {
                $this->storeUserAvatar($user, $data['avatar']);
            }

            // Entry in the change history.
            $newData = User::with('settings')->where('id', '=', $user->id)->first();
            event(
                new UserChanged(
                    $oldData,
                    $newData,
                    EHistoryActionType::UPDATE
                )
            );

            return $user->fresh();
        });
    }

    private function storeUserAvatar(User $user, UploadedFile $avatar): void
    {
        $oldData = User::with('settings')->where('id', '=', $user->id)->first();
        $oldData->history_comment = null;

        $disk = config('disk.users_avatars.disk');

        $manager = new ImageManager(new Driver);

        $image = $manager->decode($avatar->getRealPath());

        $image->cover(256, 256);

        $encoded = $image->encodeUsingFormat(
            Format::WEBP,
            quality: 85,
        );

        Storage::disk($disk)->put(
            $this->getUserAvatarPath($user),
            $encoded->toString(),
        );

        // Entry in the change history.
        $newData = User::with('settings')->where('id', '=', $user->id)->first();
        $newData->history_comment = 'COMMENT_STORE_AVATAR';
        event(
            new UserChanged(
                $oldData,
                $newData,
                EHistoryActionType::UPDATE
            )
        );
    }

    private function deleteUserAvatar(User $user): void
    {
        $oldData = User::with('settings')->where('id', '=', $user->id)->first();
        $oldData->history_comment = null;

        $disk = config('disk.users_avatars.disk');

        Storage::disk($disk)->delete(
            $this->getUserAvatarPath($user),
        );

        // Entry in the change history.
        $newData = User::with('settings')->where('id', '=', $user->id)->first();
        $newData->history_comment = 'COMMENT_DELETE_AVATAR';
        event(
            new UserChanged(
                $oldData,
                $newData,
                EHistoryActionType::UPDATE
            )
        );
    }

    private function getUserAvatarPath(User $user): string
    {
        return "{$user->id}/avatar.webp";
    }

    public function getUserSettings(User $user)
    {
        $settings = $user->settings()
            ->pluck('setting_value', 'setting_name')
            ->toArray();

        // Returned user settings
        // TODO: Add new user settings here
        return [
            EUserSettingName::WEB_NOTIFICATIONS->value => $settings[EUserSettingName::WEB_NOTIFICATIONS->value] ?? false,
            EUserSettingName::MAIL_NOTIFICATIONS->value => $settings[EUserSettingName::MAIL_NOTIFICATIONS->value] ?? false,
        ];
    }

    public function updateUserSettings(User $user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {
            $oldData = User::with('settings')->where('id', '=', $user->id)->first();
            $oldData->history_comment = null;

            foreach ($data as $settingName => $settingValue) {
                $user->settings()->updateOrCreate(
                    [
                        'setting_name' => $settingName,
                    ],
                    [
                        'setting_value' => $settingValue,
                    ]
                );
            }

            // Entry in the change history.
            $newData = User::with('settings')->where('id', '=', $user->id)->first();
            $newData->history_comment = 'COMMENT_CHANGE_SETTINGS';
            event(
                new UserChanged(
                    $oldData,
                    $newData,
                    EHistoryActionType::UPDATE
                )
            );

            return $this->getUserSettings($user);
        });
    }
}
