<?php

return [
    /**
     * Optional: Feldname in der Customer-API Object-Response für die PATCH-UUID (z. B. asset_uuid).
     * Leer = automatische Erkennung (asset_uuid, uuid, object_id, …).
     */
    'object_uuid_key' => env('SEVENTHINGS_OBJECT_UUID_KEY', ''),

    /**
     * API-Feldkey für „Inventurhinweis“ (LONG_TEXT), z. B. custom_3 auf der HWK-Instanz.
     */
    'inventurhinweis_field_key' => env('SEVENTHINGS_INVENTURHINWEIS_FIELD_KEY', 'custom_3'),

    'client_id' => env('SEVENTHINGS_CLIENT_ID'),
    'username' => env('SEVENTHINGS_USERNAME'),
    'password' => env('SEVENTHINGS_PASSWORD'),
    'grant_type' => env('SEVENTHINGS_GRANT_TYPE','password'),
    'url' => 'https://hwk-do.seventhings.com/customer-api/',
    'version' => 'v1'
];