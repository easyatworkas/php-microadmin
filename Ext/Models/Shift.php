<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

class Shift extends Model
{
    protected $path = '/customers/{customer}/employees/{employee}/shifts';
    protected ?Employee $my_employee = null;
    protected ?CustomerSchedule $my_schedule = null;
    protected ?array $my_warnings = null;

    // Models ----------------------------------------------------------------------------------------------------------
    public function employee(): Employee
    {
        if (!is_null($this->my_employee)) return $this->my_employee;

        $this->my_employee = Employee::customer($this->getMy('customer'))->get($this->getAttribute('employee_id'));
        return $this->my_employee;
    }
    public function schedule(): CustomerSchedule
    {
        if (!is_null($this->my_schedule)) return $this->my_schedule;

        // In order to get the correct schedule, we need to cross-check against the employee's external relationships
        $external_employers = $this->employee()->externalEmployers()->from($this->from)->getAll();
        if (!$external_employers)
        {
            $this->my_schedule = CustomerSchedule::customer($this->getMy('customer'))->get($this->getAttribute('schedule_id'));
            return $this->my_schedule;
        }
        else
        {
            foreach ($external_employers as $external_employer)
            {
                // Grab the Customer model
                $customer = Customer::get($external_employer->customer_id);

                // Check if this customer has a schedule with a matching schedule_id
                $this->my_schedule = $customer->getModel(CustomerSchedule::class, 'id', $this->getAttribute('schedule_id'));
            }
        }
        return $this->my_schedule;
    }

    // Model Arrays ----------------------------------------------------------------------------------------------------
    public function warnings(): array
    {
        if (!is_null($this->my_warnings)) return $this->my_warnings;

        if (!is_array($this->getAttribute('warnings'))) {
            $withProperties = static::newQuery($this->getPath())
                ->with([ 'warnings' ])
                ->get($this->getKey());

            $this->attributes['warnings'] = $withProperties->getAttribute('warnings') ?? [];
        }

        $this->my_warnings = array_map(function (array $attributes) {
            return Warning::newInstance($attributes);
        }, $this->getAttribute('warnings'));
        return $this->my_warnings;
    }

    // Functions -------------------------------------------------------------------------------------------------------
    public function swapWith(Employee $new): bool
    {
        $schedule = $this->schedule();
        eaw()->update('/customers/'.$schedule->customer_id.'/schedules/'.$schedule->id.'/shifts/'.$this->id, null, ['employee_id' => $new->id]);
        return true; // TODO: Handle better with try-catch
    }
}
