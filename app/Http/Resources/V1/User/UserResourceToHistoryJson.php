<?php

namespace App\Http\Resources\V1\User;

use App\Http\Resources\V1\Role\RoleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResourceToHistoryJson extends JsonResource
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
            'roles' => $this->roles ? RoleResource::collection($this->roles)->pluck('name')->implode(', ') : null,
            'emailVerifiedAt' => $this->email_verified_at,
            'confirmed' => $this->confirmed,
            'avatarUrl' => $this->getAvatarUrl(),
            'deletedAt' => $this->deleted_at,

            'historyComment' => $this->history_comment,
        ];
    }

    private function getAvatarUrl(): ?string
    {
        $disk = config('disk.users_avatars.disk');
        $path = "{$this->id}/avatar.webp";

        $storage = Storage::disk($disk);

        if (! $storage->exists($path)) {
            return null;
        }

        return $storage->url($path).'?v='.$storage->lastModified($path);
    }
}
