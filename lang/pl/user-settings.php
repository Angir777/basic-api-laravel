<?php

use App\Enums\EUserSettingName;

return [
    'name' => [
        EUserSettingName::WEB_NOTIFICATIONS->value => 'Powiadomienia w aplikacji',
        EUserSettingName::MAIL_NOTIFICATIONS->value => 'Powiadomienia na adres email',
    ],
    'description' => [
        EUserSettingName::WEB_NOTIFICATIONS->value => 'Otrzymywanie powiadomień wyświetlanych w aplikacji.',
        EUserSettingName::MAIL_NOTIFICATIONS->value => 'Otrzymywanie powiadomień na połączony adres email.',
    ],
];
