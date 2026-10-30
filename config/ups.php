<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Polling
    |--------------------------------------------------------------------------
    |
    | How long to wait for a UPS network card before treating the poll as
    | failed, and when a unit's last reading stops counting as current.
    |
    */

    'timeout_seconds' => (int) env('UPS_TIMEOUT_SECONDS', 10),

    'stale_after_minutes' => (int) env('UPS_STALE_AFTER_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Readings older than this many days are removed by model:prune.
    |
    */

    'retention_days' => (int) env('UPS_RETENTION_DAYS', 90),

];
