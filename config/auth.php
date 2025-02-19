<?php

return [
    'clients' => [
        env('TOKEN_API_V2') => 'api.v2',
        env('TOKEN_API_V3') => 'api.v3',
        env('TOKEN_MADMIN') => 'm.admin',
        env('TOKEN_BPM') => 'bpm',
        env('TOKEN_TEST') => 'test',
        env('TOKEN_IMT_SERVICE') => 'imt_service',
        env('TOKEN_IBANK') => 'ibank',
        env('TOKEN_NPGATE') => 'npgate',
        env('TOKEN_COMMISSION_SERVICE') => 'commission_service',
        env('TOKEN_ECOMM') => 'ecomm',
        env('TOKEN_ABS_SERVICE') => 'abs_service',
    ],

    'ips' => explode(';', env('CLIENT_IPS'))
];
