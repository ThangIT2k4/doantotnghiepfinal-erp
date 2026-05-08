<?php

return [
    'vertex_ai' => [
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
        'location' => env('GOOGLE_CLOUD_LOCATION', 'us-central1'),
        'model' => env('GOOGLE_GEMINI_MODEL', 'gemini-2.0-flash-exp'),
        'api_key' => env('GOOGLE_API_KEY'),
        'credentials_json' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    ],
];
