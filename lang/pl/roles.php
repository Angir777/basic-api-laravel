<?php

use App\Enums\ERoleName;

return [
    ERoleName::SUPERADMIN->value => 'Super administrator',
    ERoleName::ADMIN->value => 'Administrator',
    ERoleName::TEACHER->value => 'Nauczyciel',
    ERoleName::STUDENT->value => 'Uczeń',
];
