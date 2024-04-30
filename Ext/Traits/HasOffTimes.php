<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\Customer;
use Ext\Models\CustomerGroup;
use Ext\Models\CustomerGroupOffTime;
use Ext\Models\CustomerOffTime;
use Ext\Models\Employee;
use Ext\Models\EmployeeOffTime;
use Ext\Models\OffTime;

trait HasOffTimes
{
    private function offTimeClass(): string
    {
        return match (getClass($this)) {
            CustomerGroup::class => CustomerGroupOffTime::class,
            Customer::class => CustomerOffTime::class,
            Employee::class => EmployeeOffTime::class,
            default => OffTime::class
        };
    }

    protected ?array $allOffTimes = null;

    public function allOffTimes(): array
    {
        return !is_null($this->allOffTimes)
            ? $this->allOffTimes
            : $this->allOffTimes = $this->client->query($this->getFullPath() . '/off_times')
                ->setModel($this->offTimeClass())
                ->getAll()
                ->all();
    }

    public function offTimes(?string $myFrom = null, ?string $myTo = null): array
    {
        if (is_null($myFrom) && is_null($myTo)) {
            return $this->allOffTimes();
        }

        $from = $myFrom ? dbTime($myFrom) : '1900-01-01';
        $to = $myTo ? dbTime($myTo) : '2100-01-01';

        return $this->client->query($this->getFullPath() . '/off_times')
            ->setModel($this->offTimeClass())
            ->from($from)
            ->to($to)
            ->getAll()
            ->all();
    }

    public function findOffTimes(array $attributes = [], ?string $myFrom = null, ?string $myTo = null, bool $allWithin = true): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($myFrom) && is_null($myTo)) {
            return $this->allOffTimes();
        }

        $found = [];

        $from = $myFrom ? dbTime($myFrom) : '1900-01-01';
        $to = $myTo ? dbTime($myTo) : '2100-01-01';

        foreach ($this->allOffTimes() as $model) {
            if ($model->matches($attributes)) {
                if (intervalWithin($model->from, $model->to, $from, $to, $allWithin)) {
                    $found[] = $model;
                }
            }
        }

        return $found;
    }

    public function getOffTime(string $key): ?OffTime
    {
        try {
            return static::newQuery($this->getFullPath() . "/off_times/$key")->setModel($this->offTimeClass())->get();
        } catch (Exception $e) {
            return null;
        }
    }

    public function offTimeHours(string $myFrom, string $myTo, array $filter = [], bool $returnString = false): float|string
    {
        $offTimes = $this->findOffTimes($filter, $myFrom, $myTo, false);

        $from = \Carbon\Carbon::parse($myFrom, $GLOBALS['my_time_zone'])->setTimezone($GLOBALS['db_time_zone']);
        $to = \Carbon\Carbon::parse($myTo, $GLOBALS['my_time_zone'])->setTimezone($GLOBALS['db_time_zone']);

        $requestedPeriod = \League\Period\Period::fromDatepoint($from, $to);

        $sequence = new \League\Period\Sequence($requestedPeriod);

        foreach ($offTimes as $offTime) {
            $sequence->push(\League\Period\Period::fromDatepoint(
                \Carbon\Carbon::parse($offTime->from, $GLOBALS['db_time_zone']),
                \Carbon\Carbon::parse($offTime->to, $GLOBALS['db_time_zone'])));
        }

        return secondsToHours($sequence->intersections()->totalTimeDuration(), $returnString);
    }
}