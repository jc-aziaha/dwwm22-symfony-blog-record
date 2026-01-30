<?php

namespace App\Trait;

trait TimeZoneTrait
{
    /**
     * Permet de changer le fuseau horaire de l'application.
     */
    protected function changeTimeZone(string $timezoneId): void
    {
        date_default_timezone_set($timezoneId);
    }
}
