<?php

use App\Enums\EUserSettingName;

return [
    'name' => [
        EUserSettingName::WEB_NOTIFICATIONS->value => 'In-app notifications',
        EUserSettingName::MAIL_NOTIFICATIONS->value => 'Email notifications',
    ],
    'description' => [
        EUserSettingName::WEB_NOTIFICATIONS->value => 'Receive notifications displayed in the app.',
        EUserSettingName::MAIL_NOTIFICATIONS->value => 'Receive notifications to your linked email address.',
    ],
];
