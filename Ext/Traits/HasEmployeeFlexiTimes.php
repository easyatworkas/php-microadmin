<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\EmployeeFlexiTime;

trait HasEmployeeFlexiTimes
{
    protected ?array $myFlexiTimes = null;

    public function flexiTimes(): array
    {
        return !is_null($this->myFlexiTimes)
            ? $this->myFlexiTimes
            : $this->myFlexiTimes = $this->client->query($this->getFullPath() . '/flexitime')->setModel(EmployeeFlexiTime::class)
                ->orderBy('business_date')
                ->direction('asc')
                ->getAll()->all();
    }

    public function findFlexiTime(array $attributes = [], string $when = 'now'): ?EmployeeFlexiTime
    {
        foreach ($this->flexiTimes() as $flexiTime) {
            if ($flexiTime->matches($attributes)) {
                if (matchesBusinessDate($when, $flexiTime->business_date, $this->employer()->time_zone)) {
                    return $flexiTime;
                }
            }
        }
        return null;
    }

    public function findFlexiTimes(array $attributes = [], string $from = null, string $to = null): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($from) && is_null($to)) {
            return $this->flexiTimes();
        }

        $foundFlexiTimes = [];
        $start = is_null($from) ? $this->from : $from;
        $end = $to;

        foreach ($this->flexiTimes() as $flexiTime) {
            if ($flexiTime->matches($attributes)) {
                if (!bdBefore($flexiTime->business_date, $from,
                        $this->employer()->time_zone) && bdBeforeOrEqual($flexiTime->business_date, $to,
                        $this->employer()->time_zone)) {
                    $foundFlexiTimes[] = $flexiTime;
                }
            }
        }
        return $foundFlexiTimes;
    }

    public function getFlexiTime(string $key): ?EmployeeFlexiTime
    {
        return $this->findFlexiTime(['id' => $key]);
    }

    public function hasFlexiTime(array $attributes = [], string $when = 'now'): bool
    {
        return (bool)$this->findFlexiTime($attributes, $when);
    }

    public function copyFlexiTime(EmployeeFlexiTime $source): ?EmployeeFlexiTime
    {
        throw new Exception('Not implemented');
    }

    public function newFlexiTime(string $businessDate, float $delta, string $comment): ?EmployeeFlexiTime
    {
        try {
            $flexiTime = EmployeeFlexiTime::newInstance(array_filter([
                'employee_id' => $this->id,
                'business_date' => $businessDate,
                'delta' => $delta,
                'comment' => $comment,
            ], static function ($var) {
                return $var !== null;
            }
            ))->setPath("/customers/$this->customer_id/employees/$this->id/flexitime");

            $flexiTime->save();

            return $flexiTime;
        } catch (Exception $e) {
            return null;
        }
    }

    public function flexiBalance(string $when = 'now', bool $returnString = false)
    {
        $balance = 0;
        $format = '%2d hours, %02d minutes';

        foreach ($this->flexiTimes() as $adjustment) {
            if (bdBeforeOrEqual($adjustment->business_date, $when, $this->employer()->time_zone)) {
                $balance += $adjustment->delta;
            }
        }

        return $returnString
            ? $balance < 0 ? '-' . sprintf($format, abs($balance), abs(fmod($balance, 1) * 60)) : sprintf($format,
                $balance, fmod($balance, 1) * 60)
            : $balance;
    }
}