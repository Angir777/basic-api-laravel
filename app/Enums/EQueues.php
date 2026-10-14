<?php

namespace App\Enums;

enum EQueues: string
{
    case REGISTER_EMAILS = 'register-emails';
    case RESET_PASSWORD_EMAILS = 'reset-password-emails';
}
