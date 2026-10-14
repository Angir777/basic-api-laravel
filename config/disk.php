<?php

return [
    'users_avatars' => [
        'driver' => env('USERS_AVATARS_DISK_DRIVER', 'local'), // local lub s3
        'disk' => env('USERS_AVATARS_DISK_NAME', 'usersAvatars'), // usersAvatars lub s3usersAvatars
    ],
];
