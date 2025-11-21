<?php

return [
    'late_grace_minutes' => env('ANOMALIES_LATE_GRACE_MINUTES', 5),
    'early_leave_grace_minutes' => env('ANOMALIES_EARLY_LEAVE_GRACE_MINUTES', 5),
    'min_overtime_minutes' => env('ANOMALIES_MIN_OVERTIME_MINUTES', 30),
    'min_session_minutes' => env('ANOMALIES_MIN_SESSION_MINUTES', 15),
    'max_unpaid_break_minutes' => env('ANOMALIES_MAX_UNPAID_BREAK_MINUTES', 120),
    'min_underwork_minutes' => env('ANOMALIES_MIN_UNDERWORK_MINUTES', 15),
];
