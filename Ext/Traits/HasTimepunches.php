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
use Ext\Models\CustomerGroupTimepunch;
use Ext\Models\CustomerTimepunch;
use Ext\Models\Employee;
use Ext\Models\EmployeeTimepunch;
use Ext\Models\Timepunch;

trait HasTimepunches
{
    private function timepunchClass(): string
    {
        return match (getClass($this)) {
            CustomerGroup::class => CustomerGroupTimepunch::class,
            Customer::class => CustomerTimepunch::class,
            Employee::class => EmployeeTimepunch::class,
            default => Timepunch::class
        };
    }

    protected ?array $allTimepunches = null;

    public function allTimepunches(): array
    {
        return !is_null($this->allTimepunches)
            ? $this->allTimepunches
            : $this->allTimepunches = $this->client->query($this->getFullPath() . '/timepunches')
                ->setModel($this->timepunchClass())
                ->getAll()
                ->all();
    }

    public function timepunches(?string $myFrom = null, ?string $myTo = null): array
    {
        if (is_null($myFrom) && is_null($myTo)) {
            return $this->allTimepunches();
        }

        $from = $myFrom ? dbTime($myFrom) : '1900-01-01';
        $to = $myTo ? dbTime($myTo) : '2100-01-01';

        return $this->client->query($this->getFullPath() . '/timepunches')
            ->setModel($this->timepunchClass())
            ->from($from)
            ->to($to)
            ->getAll()
            ->all();
    }

    public function findTimepunches(array $attributes = [], ?string $myFrom = null, ?string $myTo = null, bool $useBd = true, bool $allWithin = true): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($myFrom) && is_null($myTo)) {
            return $this->allTimepunches();
        }

        $found = [];

        if ($useBd) {
            foreach ($this->timepunches($myFrom, $myTo) as $model) {
                if ($model->matches($attributes)) {
                    $found[] = $model;
                }
            }

        } else {
            $from = $myFrom ? dbTime($myFrom) : '1900-01-01';
            $to = $myTo ? dbTime($myTo) : '2100-01-01';

            foreach ($this->allTimepunches() as $model) {
                if ($model->matches($attributes)) {
                    if (intervalWithin($model->in, $model->out, $from, $to, $allWithin)) {
                        $found[] = $model;
                    }
                }
            }
        }

        return $found;
    }

    public function getTimepunch(string $key): ?Timepunch
    {
        try {
            return static::newQuery($this->getFullPath() . "/timepunches/$key")->setModel($this->timepunchClass())->get();
        } catch (Exception $e) {
            return null;
        }
    }

    public function timepunchHours(string $myFrom, string $myTo, array $filter = [], bool $useBd = true, bool $returnString = false): float|string
    {
        $timepunches = $this->findTimepunches($filter, $myFrom, $myTo, $useBd, false);

        if ($useBd) {
            $sequence = new \League\Period\Sequence();
        } else {
            $from = \Carbon\Carbon::parse($myFrom, $GLOBALS['my_time_zone'])->setTimezone($GLOBALS['db_time_zone']);
            $to = \Carbon\Carbon::parse($myTo, $GLOBALS['my_time_zone'])->setTimezone($GLOBALS['db_time_zone']);

            $requestedPeriod = \League\Period\Period::fromDatepoint($from, $to);

            $sequence = new \League\Period\Sequence($requestedPeriod);
        }

        foreach ($timepunches as $timepunch) {
            $sequence->push(\League\Period\Period::fromDatepoint(
                \Carbon\Carbon::parse($timepunch->in, $GLOBALS['db_time_zone']),
                \Carbon\Carbon::parse($timepunch->out, $GLOBALS['db_time_zone'])));
        }

        return $useBd ? secondsToHours($sequence->totalTimeDuration(),
            $returnString) : secondsToHours($sequence->intersections()->totalTimeDuration(), $returnString);
    }
}