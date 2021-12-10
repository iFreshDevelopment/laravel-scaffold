<?php

return [
    'database' => env('FM_DATABASE'),
    'username' => env('FM_USERNAME'),
    'password' => env('FM_PASSWORD'),
    'hostname' => env('FM_HOSTNAME'),
    'port' => env('FM_PORT', 443),
    'protocol' => env('FM_PROTOCOL', 'https'),
    'debug' => env('FM_DEBUGGING_MODE' , false),
];
