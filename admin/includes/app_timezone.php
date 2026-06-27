<?php

if (!defined('PAYROLL_APP_TIMEZONE')) {
    define('PAYROLL_APP_TIMEZONE', 'Asia/Kolkata');
}

if (!defined('PAYROLL_APP_TZ_OFFSET')) {
    // Fixed offset avoids broken Asia/Kolkata mapping on some Windows/IIS PHP builds.
    define('PAYROLL_APP_TZ_OFFSET', '+05:30');
}

ini_set('date.timezone', PAYROLL_APP_TZ_OFFSET);
date_default_timezone_set(PAYROLL_APP_TZ_OFFSET);

function payroll_ist_timezone(): DateTimeZone
{
    static $tz;

    if ($tz === null) {
        $tz = new DateTimeZone(PAYROLL_APP_TZ_OFFSET);
    }

    return $tz;
}

/**
 * Current India time from the server clock (Unix instant → IST).
 */
function payroll_app_now(): DateTimeImmutable
{
    return (new DateTimeImmutable('@' . time()))->setTimezone(payroll_ist_timezone());
}

function payroll_format_today_label(?DateTimeImmutable $when = null): string
{
    return ($when ?? payroll_app_now())->format('l, j M Y');
}
