<?php

namespace App\Http\Resources\V1\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return collect($this->resource)
            ->mapWithKeys(function (bool $value, string $settingName) {
                return [
                    $settingName => [
                        'name' => $settingName,
                        'value' => $value,
                        'translatedName' => __('user-settings.name.'.$settingName),
                        'translatedDescription' => __('user-settings.description.'.$settingName),
                    ],
                ];
            })
            ->toArray();
    }
}
