<?php

return [
    'enabled' => env('PREDICTION_AUTO_ENABLED', true),
    'after_price_import' => env('PREDICTION_AUTO_AFTER_IMPORT', true),
    'python_binary' => env('PREDICTION_PYTHON_BINARY', env('PYTHON_BINARY', 'python')),
    'timeout_seconds' => (int) env('PREDICTION_TIMEOUT_SECONDS', 7200),
    'queue' => env('PREDICTION_QUEUE', 'predictions'),
    'horizons' => [1, 3, 7, 14, 30],
    'minimum_history' => (int) env('PREDICTION_MIN_HISTORY', 120),
    'minimum_test_observations' => (int) env('PREDICTION_MIN_TEST_OBSERVATIONS', 14),
    'maximum_missing_rate' => (float) env('PREDICTION_MAX_MISSING_RATE', 0.70),
    'maximum_mape' => (float) env('PREDICTION_MAX_MAPE', 15),
];
