<?php

namespace App\Services\User;

use App\Enums\EHistoryActionType;
use App\Events\UserChanged;
use App\Events\UserRegistered;
use App\Exceptions\ApiException;
use App\Helpers\Access\RefreshUserTokenHelper;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class UserService
{
    /**
     * @param  mixed  $confirmationCode
     */
    public function confirmAccount($confirmationCode): void
    {
        $user = User::where('confirmation_code', $confirmationCode)->firstOrFail();

        $oldData = User::with('settings')->where('id', '=', $user->id)->first();

        if ($user->confirmed) {
            throw new ApiException(
                message: 'ALREADY_CONFIRMED',
                status: Response::HTTP_BAD_REQUEST
            );
        }

        $user->email_verified_at = Carbon::now();
        $user->confirmed = true;

        $user->save();

        // Zapis do historii
        $newData = User::with('settings')->where('id', '=', $user->id)->first();
        event(
            new UserChanged(
                $oldData,
                $newData,
                EHistoryActionType::UPDATE
            )
        );
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $confirmed = $data['confirmed'] ?? ! config('access.confirm_email');

            // Create new user
            $user = User::create([
                'first_name' => $data['firstName'],
                'last_name' => $data['lastName'],
                'email' => $data['email'],
                'password' => isset($data['password']) ? Hash::make($data['password']) : Hash::make(Str::random()),
                'confirmed' => $confirmed,
                'email_verified_at' => $confirmed ? Carbon::now() : null,
                'confirmation_code' => md5(uniqid(mt_rand(), true)),
            ]);

            // Checking whether the logged in user can assign the 'SUPER_ADMIN' role, if one is added
            if (isset($data['roles'])) {
                foreach ($data['roles'] as $role) {
                    if ($role === config('access.roles.superadmin')) {
                        $this->checkPermissionToSuperAdmin(user: Auth::User());
                    }
                }
            }

            // Role synchronization, deleting old ones and adding new ones
            if (isset($data['roles']) && count($data['roles']) > 0) {
                $user->syncRoles($data['roles']);
            } else {
                $user->assignRole(config('access.roles.teacher'));
            }

            // Zapis do historii
            $newData = User::where('id', '=', $user->id)->first();
            event(
                new UserChanged(
                    null,
                    $newData,
                    EHistoryActionType::CREATE
                )
            );

            // Send 'welcome' mail
            event(new UserRegistered(
                user: $user,
                notificationLocale: $data['locale'],
            ));

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $oldData = User::with('settings')->where('id', '=', $user->id)->first();

            // Update user
            $user->update([
                'first_name' => $data['firstName'],
                'last_name' => $data['lastName'],
                'email' => $data['email'],
                'confirmed' => $data['confirmed'],
                'email_verified_at' => $data['confirmed'] ? ($user->email_verified_at ?? Carbon::now()) : null,
            ]);

            // Usunięcie aktualnych roli
            // $user->roles()->detach();

            // Przypisanie nowych roli
            // $user->assignRole($data['roles']);

            // Zapis do historii
            $newData = User::with('settings')->where('id', '=', $user->id)->first();
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

    public function changePassword(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $oldData = User::with('settings')->where('id', '=', $user->id)->first();
            $oldData->history_comment = null;

            // Update user password
            $user->update([
                'password' => Hash::make($data['password']),
            ]);

            // Zapis do historii
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

    public function delete(string $userId): User
    {
        $oldData = User::with('settings')->where('id', '=', $userId)->first();

        $user = User::findOrFail($userId);
        $user->delete();

        // Zapis do historii
        $newData = User::withTrashed()->with('settings')->where('id', '=', $userId)->first();
        event(
            new UserChanged(
                $oldData,
                $newData,
                EHistoryActionType::DELETE
            )
        );

        return $user;
    }

    public function restore(string $userId): User
    {
        $oldData = User::withTrashed()->with('settings')->where('id', '=', $userId)->first();

        $user = User::withTrashed()->findOrFail($userId);
        $user->restore();

        $newData = User::with('settings')->where('id', '=', $userId)->first();

        // Zapis do historii
        $newData = User::withTrashed()->with('settings')->where('id', '=', $userId)->first();
        event(
            new UserChanged(
                $oldData,
                $newData,
                EHistoryActionType::RESTORE
            )
        );

        return $user;
    }

    public function attachRoles(User $user, array $selectedRolesIds): User
    {
        return DB::transaction(function () use ($user, $selectedRolesIds) {
            $oldData = User::withTrashed()->with('settings', 'roles')->where('id', '=', $user->id)->first();

            $roles = Role::whereIn('id', $selectedRolesIds)->get();
            $user->syncRoles($roles);

            // Zapis do historii
            $newData = User::withTrashed()->with('settings', 'roles')->where('id', '=', $user->id)->first();
            event(
                new UserChanged(
                    $oldData,
                    $newData,
                    EHistoryActionType::UPDATE
                )
            );

            // Automatyczne wylogowanie
            RefreshUserTokenHelper::shouldLogoutOnNextRequest($user->id);

            return $user->fresh('roles');
        });
    }

    /**
     * Checks whether the user has the 'SUPER_ADMIN' permission
     * If there is none, it returns an error
     */
    private function checkPermissionToSuperAdmin(?User $user): void
    {
        if (! $user || $user->cannot('BASE_SUPER_ADMIN_ACCESS')) {
            throw new ApiException(
                message: 'YOU_ARE_NOT_A_SUPER_ADMIN',
                status: Response::HTTP_BAD_REQUEST
            );
        }
    }
}
