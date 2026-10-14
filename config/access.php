<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Roles
    |--------------------------------------------------------------------------
    |
    | Default roles that should always be in the application, based on them access to specific functionalities will be verified
    | e.g., admin guard has access to Pulse, Log Viewer, etc.
    |
    */
    'roles' => [
        'superadmin' => 'superadmin',
        'admin' => 'admin',
        'teacher' => 'teacher',
        'student' => 'student',
    ],

    'registration' => env('ENABLE_REGISTRATION', true),
    'confirm_email' => env('CONFIRM_EMAIL', true),
];
