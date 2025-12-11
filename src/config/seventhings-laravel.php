<?php

return [
    'client_id' => env('SEVENTHINGS_CLIENT_ID'),
    'username' => env('SEVENTHINGS_USERNAME'),
    'password' => env('SEVENTHINGS_PASSWORD'),
    'grant_type' => env('SEVENTHINGS_GRANT_TYPE','password'),
    'url' => 'https://hwk-do.seventhings.com/customer-api/',
    'version' => 'v1'
];