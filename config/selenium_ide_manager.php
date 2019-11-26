<?php

return [
    'storage' => env('SELENIUM_STOGATE', 'public'), //public, s3,
    'middleware' => ['web'],
    'google_spreadsheets_id' => '',
    'test_case_sheet_id' => '',
    'google_application_credentials' => []
];
