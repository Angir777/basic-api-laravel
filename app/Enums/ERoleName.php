<?php

namespace App\Enums;

enum ERoleName: string
{
    /**
     * Super administrator
     */
    case SUPERADMIN = 'superadmin';

    /**
     * Administrator
     */
    case ADMIN = 'admin';

    /**
     * Nauczyciel
     */
    case TEACHER = 'teacher';

    /**
     * Uczeń
     */
    case STUDENT = 'student';
}
