<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Anthropic Claude Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Anthropic Claude AI service.
    |
    */

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
        'timeout' => env('ANTHROPIC_TIMEOUT', 60),
    ],

];
