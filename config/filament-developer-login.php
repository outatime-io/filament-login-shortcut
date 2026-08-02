<?php

declare(strict_types=1);

use App\Models\User;

return [
    'user_model' => User::class,
    'search' => [
        'columns' => ['email'],
        'result_limit' => 20,
        'hard_maximum' => 100,
        'minimum_length' => 1,
        'debounce' => 300,
    ],
    'rate_limits' => ['searches_per_minute' => 60, 'logins_per_minute' => 10, 'denials_per_minute' => 20],
    'audit' => ['log_channel' => null, 'log_ip_addresses' => false],
];
