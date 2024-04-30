<?php // TODO: Not refactored

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasProperties;

/**
 * @property mixed|null $schedule_id
 * @property mixed|null $employee_id
 * @property mixed|null $offset
 * @property mixed|null $length
 * @property mixed|null $business_date
 * @property mixed|null $edited_by
 * @property mixed|null $from
 * @property mixed|null $to
 * @property mixed|null $all_qualifications
 * @property mixed|null $original_id
 * @property mixed|null $changes
 * @property mixed|null $net_length
 */
class EmployeeShift extends Model
{
    use HasProperties;

    protected $path = '/customers/{customer}/employees/{employee}/shifts';
    protected ?Customer $my_customer = null;
    protected ?Employee $my_employee = null;
    protected ?CustomerSchedule $my_schedule = null;
    protected ?ShiftPeriod $my_break = null;
    protected ?array $my_periods = null;

    // Models ----------------------------------------------------------------------------------------------------------
    public function customer(): Customer
    {
        return !is_null($this->my_customer)
            ? $this->my_customer
            : $this->my_customer = Customer::get($this->getIdOf('customer'));
    }
    public function employee(): Employee
    {
        return !is_null($this->my_employee)
            ? $this->my_employee
            : $this->my_employee = is_null($this->employee_id) ? null : Employee::customer($this->getIdOf('customer'))->get($this->employee_id);
    }
    public function schedule(): ?CustomerSchedule
    {
        $parent = $this->getParent();

        switch ( get_class($parent) )
        {
            case Employee::class:
                if (is_null($this->my_employee)) $this->my_employee = $parent;

                // In order to get the correct schedule, we need to cross-check against the employee's external relationships | TODO: Fix this by building new query?
                $external_employers = $this->employee()->externalEmployers()->from($this->from)->getAll();
                if (!$external_employers)
                {
                    return $this->my_schedule = CustomerSchedule::customer($this->getIdOf('customer'))->get($this->getAttribute('schedule_id'));
                }
                else
                {
                    foreach ($external_employers as $external_employer)
                    {
                        // Grab the Customer model
                        $customer = Customer::get($external_employer->customer_id);

                        // Check if this customer has a schedule with a matching schedule_id
                        $schedule = $customer->getModel(CustomerSchedule::class, 'id', $this->getAttribute('schedule_id'));
                        if (!is_null($schedule)) {
                            return $this->my_schedule = $schedule;
                        }
                    }
                }
                return $this->my_schedule;
            case CustomerSchedule::class:
                if (is_null($this->my_schedule)) $this->my_schedule = $parent;
                return $this->my_schedule;
            default:
                return null;
        }
    }

    // QueryBuilders ---------------------------------------------------------------------------------------------------
    public function periods(): array
    {
        return !is_null($this->my_periods)
            ? $this->my_periods
            : $this->my_periods = $this->client->query('/customers/' . $this->customer()->id . '/schedules/' . $this->schedule()->id . '/shifts/' . $this->id .'/periods')
                ->setModel(ShiftPeriod::class)
                ->getAll()
                ->all();
    }

    // Non-Model Arrays ------------------------------------------------------------------------------------------------
    public function warnings(): array
    {
        return $this->getAttribute('warnings') ?? [];
    }
    public function comments(): array
    {
        return $this->getAttribute('comments') ?? [];
    }
    public function changes(): array
    {
        return $this->getAttribute('ghosts') ?? [];
    }
    public function swaps(): array
    {
        return $this->getAttribute('swaps') ?? [];
    }

    // Functions -------------------------------------------------------------------------------------------------------
    public function swapWith( Employee $new ): bool
    {
        $schedule = $this->schedule();

        eaw()->update('/customers/'.$schedule->customer_id.'/schedules/'.$schedule->id.'/shifts/'.$this->id, null, ['employee_id' => $new->id]);

        return true; // TODO: Handle better with try-catch
    }
    public function hasBreak(): ?ShiftPeriod
    {
        if ($this->my_break) return $this->my_break;

        foreach ( $this->periods() as $period )
        {
            if ($period->break) return $this->my_break = $period;
        }

        return null;
    }
    public function addBreak( int $length = 1800 ): bool
    {
        if ($this->hasBreak()) return false;

        $offset = ($this->length / 2) - ($length / 2);

        $break = ShiftPeriod::newInstance([
            'length' => $length,
            'offset' => $offset,
            'break' => true,
            'unproductive' => true,
            'color' => '#9e9e9e',
            'description' => 'Break',
        ])->setPath('/customers/' . $this->customer()->id . '/schedules/' . $this->schedule()->id . '/shifts/' . $this->id .'/periods');

        $break->save();

        $this->my_break = $break;

        return true; // TODO: Handle better with try-catch
    }
}
