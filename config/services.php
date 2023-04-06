<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the configuration for external services.
    | 
    | Please keep the list in key => [array] format and alphabetized for ease of use.
    |
    */

    'bytespree' => [
        'css_url' => env('BYTESPREE_CSS_URL'),
    ],

    'digitalocean' => [
        'pg_version' => env('DIGITAL_OCEAN_PG_VERSION', '12'),
        'spaces' => [
            'key' => env('DIGITAL_OCEAN_SPACES_KEY'),
            'secret' => env('DIGITAL_OCEAN_SPACES_SECRET'),
        ],
        'token' => env('DIGITAL_OCEAN_TOKEN'),
    ],

    'dmiux' => [
        'url' => env('DMIUX_URL'),
    ],

    'file_upload' => [
        'url' => env('FILE_UPLOAD_URL'),
    ],

    'intercom' => [
        'secret' => env('INTERCOM_SECRET'),
    ],

    'jenkins' => [
        'region_url' => rtrim(env('REGION_JENKINS_URL', ''), '/'),
        'build_id' => env('BUILD_NUMBER'),
        'jenkins_home' => env('JENKINS_HOME'),
        'job_name' => env('JOB_NAME'),
        'job_base_name' => env('JOB_BASE_NAME'),
        'workspace' => env('WORKSPACE'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
        'secret' => env('MAILGUN_SECRET'),
    ],

    'postmark' => [
        'api_key' => env('POSTMARK_API_KEY'),
    ],

    'rollbar' => [
        'access_token' => env('ROLLBAR_ACCESS_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
    ],

    'upload' => [
        'url' => env('FILE_UPLOAD_URL'),
    ],
];
