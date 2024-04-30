<?php // TODO: Make this into trait HasShifts?

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

use Exception;
use Carbon\Carbon;

/**
 * @property mixed|null $customer_id
 * @property mixed|null $name
 * @property mixed|null $length
 * @property mixed|null $from
 * @property mixed|null $to
 * @property mixed|null $is_template
 * @property mixed|null $template_name
 * @property mixed|null $is_published
 * @property mixed|null $published_at
 */
class CustomerSchedule extends Model
{
    protected $path = '/customers/{customer}/schedules';

    // -----------------------------------------------------------------------------------------------------------------
    protected ?Customer $myOwner = null;
    public function owner(): Customer
    {
        return !is_null($this->myOwner)
            ? $this->myOwner
            : $this->myOwner = Customer::get($this->getAttribute('customer_id'));
    }

    // -----------------------------------------------------------------------------------------------------------------
    protected ?array $myShifts = null;
    public function shifts(): array
    {
        return !is_null($this->myShifts)
            ? $this->myShifts
            : $this->myShifts = $this->client->query($this->getFullPath() . '/shifts')
                ->setModel(CustomerShift::class)
                ->with([ 'periods', 'qualifications', 'warnings', 'comments' ]) // , 'swaps', 'ghosts'
                ->getAll()
                ->all();
    }

    public function getShift(string $key): ?CustomerShift
    {
        foreach ($this->shifts() as $model) {
            if ($model->{$model->keyName} == $key) {
                return $model;
            }
        }
        return null;
    }

    public function findShift(array $attributes, string $when = 'now'): ?CustomerShift
    {
        foreach ($this->shifts() as $model) {
            if ($model->matches($attributes)) {
                if (dateTimeWithin($when, $model->from, $model->to)) {
                    return $model;
                }
            }
        }
        return null;
    }

    public function findShifts(array $attributes, string $localFrom = null, string $localTo = null, bool $strict = true): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($localFrom) && is_null($localTo)) {
            return $this->shifts();
        }

        $found = [];

        foreach ($this->shifts() as $model) {
            if ($model->matches($attributes)) {
                if (intervalWithin($model->from, $model->to, utcDateTimeString($localFrom, $this->owner()->time_zone), utcDateTimeString($localTo, $this->owner()->time_zone), $strict)) {
                    $found[] = $model;
                }
            }
        }
        return $found;
    }

    // Getters ---------------------------------------------------------------------------------------------------------

    public function getShiftsWith( array $parameters, ?string $atPointInTime = 'now' ): array
    {
        $shifts = [];

        foreach ($this->shifts() as $shift) {
            if ($shift->matches($parameters)) {
                if (dateTimeWithin($atPointInTime, $shift['from'], $shift['to'])) $shifts[] = $shift;
            }
        }

        return $shifts;
    }

    // Internal Checkers -----------------------------------------------------------------------------------------------
    public function hasShiftWith( array $parameters, ?string $atPointInTime = 'now' ): bool
    {
        foreach ($this->shifts() as $shift) {
            if ($shift->matches($parameters)) {
                if (dateTimeWithin($atPointInTime, $shift['from'], $shift['to'])) return true;
            }
        }

        return false;
    }

    // Internal Modifiers ----------------------------------------------------------------------------------------------
    public function addShift( CustomerShift $shift ): bool
    {
        if (!$this->hasShiftWith([ 'from' => $shift->offset, 'to' => $shift->length, 'employee_id' => $shift->employee_id ]))
        {
            eaw()->create($this->getFullPath() . '/shifts', null, array_filter([
                'employee_id' => $shift->employee_id,
                'offset' => $shift->offset,
                'length' => $shift->length,
                'business_date' => $shift->business_date,
                'qualifications' => $shift->qualifications,
            ], static function($var) {return $var !== null;} ));

            return true;
        }

        return false;
    }
    public function createShift( string $businessDate, string $localFrom, string $localTo, ?int $employeeId = null, ?int $businessUnitId = null, array $qualificationIds = [] ): ?CustomerShift
    {
        $tz = $this->owner()->time_zone;
        $utcFrom = utcDateTimeString($localFrom, $tz);
        $utcTo = utcDateTimeString($localTo, $tz);

        $qualifications = [];

        // Return null if trying to create Shift outside the range of this Schedule
        if (!intervalWithin($utcFrom, $utcTo, $this->from, $this->to)) return null;

        // Return null if Employee does not exist
        if (!is_null($employeeId) && !$this->owner()->hasEmployee([ 'id' => $employeeId ])) return null;

        // Ignore Qualifications that don't exist
        foreach ($qualificationIds as $qualification)
        {
            if ($this->owner()->hasQualificationWith([ 'id' => $qualification ])) $qualifications[] = $qualification;
        }

        // Calculate Offset
        $offset = Carbon::parse($this->from)->diffInSeconds(Carbon::parse($utcFrom));

        // Calculate Length
        $length = Carbon::parse($utcFrom)->diffInSeconds(Carbon::parse($utcTo));

        try
        {
            $shift = CustomerShift::newInstance(array_filter([
                'employee_id' => $employeeId,
                'offset' => $offset,
                'length' => $length,
                'business_date' => $businessDate,
                'qualifications' => $qualifications,
            ], static function($var)
                {return $var !== null;} )
            )->setPath($this->getFullPath() . '/shifts');

            $shift->save();

            // Handle BusinessUnit
            if (!is_null($businessUnitId) && $this->owner()->hasBusinessUnit([ 'id' => $businessUnitId ]))
            {
                eaw()->create($this->getFullPath() . "/shifts/$shift->id/periods", null, [
                    'business_unit_id' => $businessUnitId,
                    'length' => $length,
                    'offset' => 0,
                ]);
            }

            return $shift;
        }
        catch (Exception $e)
        {
            logg()->info($GLOBALS['error'] . $e->getCode() . ': ' . $e->getMessage() . PHP_EOL);
            return null;
        }
    }
}
