<?php

namespace App\Enums;

enum EUserSettingName: string
{
    case WEB_NOTIFICATIONS = 'webNotifications';
    case MAIL_NOTIFICATIONS = 'mailNotifications';
}
