<?php

return [
    'users_avatars' => [
        'driver' => env('USERS_AVATARS_DISK_DRIVER', 'local'), // local or s3
        'disk' => env('USERS_AVATARS_DISK_NAME', 'usersAvatars'), // usersAvatars or s3usersAvatars
    ],
];
