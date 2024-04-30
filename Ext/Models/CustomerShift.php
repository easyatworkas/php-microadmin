<?php // TODO: Not refactored

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasProperties;

/**
 * @property mixed|null $id
 * @property mixed|null $schedule_id
 * @property mixed|null $employee_id
 * @property mixed|null $offset
 * @property mixed|null $length
 * @property mixed|null $business_date
 * @property mixed|null $edited_by
 * @property mixed|null $from
 * @property mixed|null $to
 * @property mixed|null $all_qualifications         array
 * @property mixed|null $original_id
 * @property mixed|null $changes
 * @property mixed|null $net_length
 */
class CustomerShift extends Model
{
    use HasProperties;

    protected $path = '/customers/{customer}/shifts';

    public function customer(): Customer
    {
        return Customer::get($this->getIdOf('customer'));
    }

    public function employee(): ?Employee
    {
        return is_null($this->employee_id) ? null : $this->customer()->getEmployee($this->employee_id);
    }

    public function schedule(): ?CustomerSchedule
    {
        return is_null($this->schedule_id) ? null : $this->customer()->getScheduleOrTemplate($this->schedule_id);
    }

    // TODO: Refactor to use HasPeriods trait
    public function periods(): array
    {
        return $this->client->query('/customers/' . $this->customer()->id . '/schedules/' . $this->schedule()->id . '/shifts/' . $this->id .'/periods')
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
        foreach ($this->periods() as $period)
        {
            if ($period->break) return $period;
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

        return true; // TODO: Handle better with try-catch; also return the ShiftPeriod
    }
}
