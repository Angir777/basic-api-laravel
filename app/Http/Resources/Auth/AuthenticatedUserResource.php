<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AuthenticatedUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'email' => $this->email,
            'token' => $this->token,
            'permissions' => $this->getAllPermissions()->pluck('name')->toArray(),
            'roles' => $this->roles()->pluck('name')->toArray(),
            'avatarUrl' => $this->getAvatarUrl(),
            'settings' => $this->settings->mapWithKeys(
                fn ($setting) => [$setting->setting_name => $setting->setting_value]
            )->toArray(),
        ];
    }

    private function getAvatarUrl(): ?string
    {
        $disk = config('disk.users_avatars.disk');
        $path = "{$this->id}/avatar.webp";

        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        return Storage::disk($disk)->url($path).'?v='.$this->updated_at?->timestamp;
    }
}
