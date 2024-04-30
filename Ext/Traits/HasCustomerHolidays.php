<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\CustomerHoliday;

trait HasCustomerHolidays
{
    public function holidays(?string $from = null, ?string $to = null, array $only = [], array $except = []): array
    {
        $start = is_null($from) ? date('Y-m-d', strtotime('first day of january this year')) : $from;
        $end = is_null($to) ? date('Y-m-d', strtotime('last day of december this year')) : $to;

        $holidays = [];

        $allHolidays = static::newQuery($this->getFullPath() . '/holidays')->setModel(CustomerHoliday::class)
            ->from($start)
            ->to($end)
            ->getAll()
            ->all();

        if (empty($only) && empty($except)) return $allHolidays;

        foreach ($allHolidays as $holiday) {
            // Don't add if found in $except
            if (in_array($holiday->name, $except)) continue;

            if (empty($only)) {
                $holidays[] = $holiday;
            } else {
                if (in_array($holiday->name, $only)) $holidays[] = $holiday;
            }
        }

        return $holidays;
    }

    public function resolvedHolidays(?string $from = null, ?string $to = null): array
    {
        $start = is_null($from) ? date('Y-m-d', strtotime('first day of january this year')) : $from;
        $end = is_null($to) ? date('Y-m-d', strtotime('last day of december this year')) : $to;

        $holidays = [];
        $resolvedHolidays = json_decode($this->resolveSetting('payroll.only_holidays')) ?? [];

        $allHolidays = static::newQuery($this->getFullPath() . '/holidays')->setModel(CustomerHoliday::class)
            ->from($start)
            ->to($end)
            ->getAll()
            ->all();

        if (empty($resolvedHolidays)) return $allHolidays;

        foreach ($allHolidays as $holiday) {

            // Only add if found in $resolvedHolidays
            if (in_array($holiday->name, $resolvedHolidays)) $holidays[] = $holiday;
        }

        return $holidays;
    }
}