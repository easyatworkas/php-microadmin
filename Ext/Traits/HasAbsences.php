<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\Absence;
use Ext\Models\Customer;
use Ext\Models\CustomerAbsence;
use Ext\Models\CustomerGroup;
use Ext\Models\CustomerGroupAbsence;
use Ext\Models\Employee;
use Ext\Models\EmployeeAbsence;

trait HasAbsences
{
    private function absenceClass(): string
    {
        return match (getClass($this)) {
            CustomerGroup::class => CustomerGroupAbsence::class,
            Customer::class => CustomerAbsence::class,
            Employee::class => EmployeeAbsence::class,
            default => Absence::class
        };
    }

    public function allAbsences(): array
    {
        return static::newQuery($this->getFullPath() . '/absences')
                ->setModel($this->absenceClass())
                ->getAll()
                ->all();
    }

    public function absences(?string $myFrom = null, ?string $myTo = null): array
    {
        if (is_null($myFrom) && is_null($myTo)) {
            return $this->allAbsences();
        }

        $from = $myFrom ? dbTime($myFrom) : '1900-01-01';
        $to = $myTo ? dbTime($myTo) : '2100-01-01';

        return static::newQuery($this->getFullPath() . '/absences')
            ->setModel($this->absenceClass())
            ->from($from)
            ->to($to)
            ->getAll()
            ->all();
    }

    public function findAbsences(array $attributes = [], ?string $myFrom = null, ?string $myTo = null, bool $allWithin = true): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($myFrom) && is_null($myTo)) {
            return $this->allAbsences();
        }

        $found = [];

        $from = $myFrom ? dbTime($myFrom) : '1900-01-01';
        $to = $myTo ? dbTime($myTo) : '2100-01-01';

        foreach ($this->allAbsences() as $model) {
            if ($model->matches($attributes)) {
                if (intervalWithin($model->from, $model->to, $from, $to, $allWithin)) {
                    $found[] = $model;
                }
            }
        }

        return $found;
    }

    public function getAbsence(string $key): ?Absence
    {
        $absence = null;
        try {
            $absence = static::newQuery($this->getFullPath() . "/absences/$key")
                ->setModel($this->absenceClass())
                ->get();
        } catch (Exception $e) {
            // TODO: Log error
        }
        return $absence;
    }

    public function absenceHours(string $myFrom, string $myTo, array $filter = [], bool $returnString = false): float|string
    {
        $absences = $this->findAbsences($filter, $myFrom, $myTo, false);

        $from = \Carbon\Carbon::parse($myFrom, $GLOBALS['my_time_zone'])->setTimezone($GLOBALS['db_time_zone']);
        $to = \Carbon\Carbon::parse($myTo, $GLOBALS['my_time_zone'])->setTimezone($GLOBALS['db_time_zone']);

        $requestedPeriod = \League\Period\Period::fromDatepoint($from, $to);

        $sequence = new \League\Period\Sequence($requestedPeriod);

        foreach ($absences as $timepunch) {
            $sequence->push(\League\Period\Period::fromDatepoint(
                \Carbon\Carbon::parse($timepunch->from, $GLOBALS['db_time_zone']),
                \Carbon\Carbon::parse($timepunch->to, $GLOBALS['db_time_zone'])));
        }

        return secondsToHours($sequence->intersections()->totalTimeDuration(), $returnString);
    }
}