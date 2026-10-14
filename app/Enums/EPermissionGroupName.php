<?php

namespace App\Enums;

enum EPermissionGroupName: string
{
    case ACCESS = 'ACCESS';
    case USERS = 'USERS';
    case ROLES = 'ROLES';
    case PERMISSIONS = 'PERMISSIONS';
}
