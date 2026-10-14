<?php

namespace App\Enums;

enum EHistoryActionType: string
{
    case CREATE = 'CREATE';
    case UPDATE = 'UPDATE';
    case DELETE = 'DELETE';
    case RESTORE = 'RESTORE';
    case COMMENT = 'COMMENT';
}
