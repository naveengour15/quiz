<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID & Status
    |--------------------------------------------------------------------------
    */
    'enabled' => env('FIREBASE_ENABLED', true),
    'project_id' => env('FIREBASE_PROJECT_ID', 'fsrquiz'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Service Account Direct Credentials (from .env)
    |--------------------------------------------------------------------------
    */
    'client_email' => env('FIREBASE_CLIENT_EMAIL'),
    'private_key' => env('FIREBASE_PRIVATE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Service Account JSON Credentials Path (Fallback)
    |--------------------------------------------------------------------------
    */
    'credentials_path' => env('FIREBASE_CREDENTIALS_PATH', storage_path('app/firebase/firebase_credentials.json')),

    /*
    |--------------------------------------------------------------------------
    | Firebase Web & App Client Keys
    |--------------------------------------------------------------------------
    */
    'app_key' => env('FIREBASE_APP_KEY', 'AIzaSyCuPnEty4CdFAA6ueQSqsTKpn8GYfmGr0c'),
    'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID', '145585273968'),
    'app_id' => env('FIREBASE_APP_ID', '1:145585273968:android:a71813c8159d0c8f2508b5'),
    'vapid_key' => env('FIREBASE_VAPID_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | Firebase Legacy Server Key (Optional fallback)
    |--------------------------------------------------------------------------
    */
    'server_key' => env('FIREBASE_SERVER_KEY', null),

    /*
    |--------------------------------------------------------------------------
    | Firebase Default Topic
    |--------------------------------------------------------------------------
    */
    'topic' => env('FIREBASE_TOPIC', 'quiz_updates'),
];
