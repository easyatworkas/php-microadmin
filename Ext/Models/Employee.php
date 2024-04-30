<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

use Carbon\Carbon;
use Exception;
use Ext\Traits\HasAbsences;                 // OK
use Ext\Traits\HasAvailabilities;           // OK
use Ext\Traits\HasCustomProperties;
use Ext\Traits\HasEmployeeAvailabilities;   // OK
use Ext\Traits\HasEmployeeComments;
use Ext\Traits\HasEmployeeContracts;
use Ext\Traits\HasEmployeeFlexiTimes;
use Ext\Traits\HasEmployeeHrFiles;
use Ext\Traits\HasEmployeePaidTimes;
use Ext\Traits\HasEmployeePayRates;
use Ext\Traits\HasEmployeePositions;
use Ext\Traits\HasEmployeeQualification;
use Ext\Traits\HasEmployeeShifts;
use Ext\Traits\HasEmployeeTimepunches;      // OK
use Ext\Traits\HasFamilyMembers;
use Ext\Traits\HasOffTimes;                 // OK
use Ext\Traits\HasTimepunches;              // OK
use Ext\Traits\HasProperties;

/**
 * @property mixed|null $id                     PRIMARY KEY             OK
 * @property mixed|null $name
 * @property mixed|null $number
 * @property mixed|null $email
 * @property mixed|null $phone
 * @property mixed|null $birth_date
 * @property mixed|null $gender
 * @property mixed|null $address1
 * @property mixed|null $address2
 * @property mixed|null $postal_code
 * @property mixed|null $city
 * @property mixed|null $country
 * @property mixed|null $from
 * @property mixed|null $to
 * @property mixed|null $seniority
 * @property mixed|null $extra_seniority
 * @property mixed|null $country_key            FOREIGN KEY
 * @property mixed|null $nationality
 * @property mixed|null $region_id              FOREIGN KEY (optional)  OK
 * @property mixed|null $user_id                FOREIGN KEY (optional)  OK
 * @property mixed|null $customer_id            FOREIGN KEY (optional)  OK
 * @property mixed|null $role_id                FOREIGN KEY (optional)  OK
 */
class Employee extends Model
{
    use HasAbsences;                // OK
    use HasAvailabilities;          // OK
    use HasEmployeeAvailabilities;  // OK
    use HasEmployeeComments;
    use HasEmployeeContracts;
    use HasEmployeeFlexiTimes;
    use HasEmployeeHrFiles;
    use HasEmployeePaidTimes;
    use HasEmployeePayRates;
    use HasEmployeePositions;
    use HasEmployeeQualification;
    use HasEmployeeShifts;
    use HasEmployeeTimepunches;     // OK
    use HasFamilyMembers;
    use HasTimepunches;             // OK
    use HasOffTimes;                // OK
    use HasProperties;
    use HasCustomProperties;        // TODO: Needed?

    protected $path = '/customers/{customer}/employees';

    /**
     * Gets Customer Model from property $customer_id
     *
     * @return \Ext\Models\Customer
     * @author Torbjørn Kallstad
     */
    public function employer(): Customer
    {
        return Customer::get($this->getIdOf('customer'));
    }

    /**
     * Gets User Model from optional property $user_id
     *
     * @return \Ext\Models\User|null
     * @author Torbjørn Kallstad
     */
    public function user(): ?User
    {
        return is_null($this->user_id) ? null : User::get($this->user_id);
    }

    /**
     * Gets CustomerRole Model from optional property $role_id
     *
     * @return \Ext\Models\CustomerRole|null
     * @author Torbjørn Kallstad
     */
    public function role(): ?CustomerRole
    {
        return is_null($this->role_id) ? null : $this->employer()->getRole($this->role_id);
    }

    /**
     * Gets Country Model from optional property $country_key
     *
     * @return \Ext\Models\Country|null
     * @author Torbjørn Kallstad
     */
    public function country(): ?Country
    {
        return is_null($this->country_key) ? null : Country::get($this->country_key);
    }

    /**
     * Gets CountryRegion Model from optional property $region_id
     *
     * @return \Ext\Models\CountryRegion|null
     * @author Torbjørn Kallstad
     */
    public function region(): ?CountryRegion
    {
        return is_null($this->region_id) ? null : $this->country()->getRegion($this->region_id);
    }



















    public function setUser(User $user): bool
    {
        // Return false if same user is already set
        if ($this->user_id == $user->id)
        {
            addToLog("Employee [$this->id] is already connected to user [$user->id]");
            return false;
        }

        try {
            $this->update([ 'user_id' => $user->id ]);
            addToLog("Employee [$this->id] is now connected to user [$user->id]");
            return true;
        } catch (Exception $e) {
            addToLog("Failed to connect Employee [$this->id] user [$user->id]: " . $e->getMessage());
            return false;
        }
    }

    public function terminate(string $when = 'now'): bool
    {
        $to = $when == 'now' ? utcNow() : $when;

        try {
            $this->update([ 'to' => $to ]);
            addToLog("Employee [$this->id] terminated [$to]");
            return true;
        } catch (Exception $e) {
            addToLog("Failed to terminate Employee [$this->id] [$to]: " . $e->getMessage());
            return false;
        }
    }

    protected ?EmployeeEmergencyContact $myEmergencyContact = null;
    public function emergencyContact(): EmployeeEmergencyContact
    {
        return !is_null($this->myEmergencyContact)
            ? $this->myEmergencyContact
            : $this->myEmergencyContact = $this->client->query($this->getFullPath() .
                '/emergency_contact')->setModel(EmployeeEmergencyContact::class)->get();
    }
    public function setEmergencyContact(string $name, string $phone, string $relation = null): bool
    {
        // TODO: Check if exists, if so, update, if not, create new
        try {
            eaw()->create($this->getFullPath() . '/emergency_contact', null, array_filter([
                'name' => $name,
                'phone' => $phone,
                'relation' => $relation,
            ], static function ($var) {
                return $var !== null;
            }));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }


    // External Employees ----------------------------------------------------------------------------------------------
    protected ?array $myExternalEmployees = null;

    public function externalEmployees(): array
    {
        return !is_null($this->myExternalEmployees)
            ? $this->myExternalEmployees
            : $this->myExternalEmployees = $this->client->query($this->getFullPath() . '/external_employees')->setModel(EmployeeExternalEmployee::class)->getAll()->all();
    }




    public function totalHours(string $localFrom, string $localTo, string $basis = 'paidtime', bool $useBd = true): float
    {
        switch ($basis)
        {
            case 'paidtime':
                $data = $this->findPaidTimes([], $localFrom, $localTo, true, $useBd);
                break;
            case 'timepunches':
                $data = $this->findTimepunches([], $localFrom, $localTo);
                break;
            case 'shifts':
                $data = $this->findShifts([], $localFrom, $localTo);
                break;
            default:
                return 0;
        }

        $sum = 0.0;

        foreach ($data as $interval)
        {
            $sum += Carbon::parse($interval->from)->diffInSeconds(Carbon::parse($interval->to)) / 3600;
        }

        return $sum;
    }

    public function totalDays(string $localFrom, string $localTo, string $basis = 'paidtime', bool $useBd = true): int
    {
        switch ($basis)
        {
            case 'paidtime':
                $data = $this->findPaidTimes([], $localFrom, $localTo);
                break;
            case 'timepunches':
                $data = $this->findTimepunches([], $localFrom, $localTo);
                break;
            case 'shifts':
                $data = $this->findShifts([], $localFrom, $localTo);
                break;
            default:
                return 0;
        }

        $days = [];

        foreach ($data as $interval)
        {
            $days[] = substr($interval->from, 0, 10);
            $days[] = substr($interval->to, 0, 10);
        }

        return count(array_unique($days));
    }


    // Not refactored ==================================================================================================
    public function hoursWorked(string $from, string $to, bool $ignore_bd = true, $basis = 'paidtime'): ?array
    {
        $include_from = localDateString($from);
        $exclude_to = '';

        // If we ignore business-date, we need to include the day before, so that hours stretching into the first
        // day that we actually want can be handled, since backend only looks at business-dates when parsing '->from()'
        // This extra day will be removed before result is returned.
        if ($ignore_bd) {
            $include_from = date_create(localDateString($from))->modify('-1 days')->format('Y-m-d');
            $exclude_to = date_create(localDateString($to))->modify('+1 days')->format('Y-m-d');
        }

        $hours = [];

        switch ($basis) {
            case 'paidtime':

                // If we ignore business-date, we need to order by 'from' instead of 'business_date'
                if ($ignore_bd) {
//                    $data = $this->paidTimes()->from($include_from)->to(localDateString($to))->orderBy('from')->direction('asc')->getAll();
                    $data = $this->findPaidTimes([], $include_from, $to);
                } else {
//                    $data = $this->paidTimes()->from($include_from)->to(localDateString($to))->orderBy('business_date')->direction('asc')->getAll();
                    $data = $this->findPaidTimes([], $include_from, $to);
                }

                foreach ($data ?? [] as $paidtime) {
                    $intervals = [];

                    if ($ignore_bd) {
                        // Check if Start Date and End Date is different
                        if (localDateString($paidtime->from) !== localDateString($paidtime->to)) {
                            // Tos ending at midnight is converted to 24:00:00 the day before
                            if (localTimeString($paidtime->to) == '00:00:00') {
                                $date = date_create(localDateString($paidtime->to))->modify('-1 days')->format('Y-m-d');
                                $end = '24:00:00';
                                $intervals[$date][0] = ['start' => localTimeString($paidtime->from), 'end' => $end];
                                break;
                            } else // Range passes midnight, and will be split up
                            {
                                // The day the work started
                                $start_date = localDateString($paidtime->from);
                                $start_date_start_time = localTimeString($paidtime->from);
                                $start_date_end_time = '24:00:00';

                                // The day the work ended
                                $end_date = localDateString($paidtime->to);
                                $end_date_start_time = '00:00:00';
                                $end_date_end_time = localTimeString($paidtime->to);

                                // Add both days to the $intervals array
                                $intervals[$start_date][0] = [
                                    'start' => $start_date_start_time,
                                    'end' => $start_date_end_time
                                ];
                                $intervals[$end_date][0] = [
                                    'start' => $end_date_start_time,
                                    'end' => $end_date_end_time
                                ];
                            }
                        } else {
                            // All in a day's work
                            $intervals[localDateString($paidtime->from)][0] = [
                                'start' => localTimeString($paidtime->from),
                                'end' => localTimeString($paidtime->to)
                            ];
                        }
                    } else {
                        $businessdate = localDateString($paidtime->business_date);

                        if (key_exists($businessdate, $intervals)) {
                            $intervals[$businessdate][count($intervals[$businessdate])] = [
                                'start' => localDateTimeString($paidtime->from),
                                'end' => localDateTimeString($paidtime->to)
                            ];
                        } else {
                            $intervals[$businessdate][0] = [
                                'start' => localDateTimeString($paidtime->from),
                                'end' => localDateTimeString($paidtime->to)
                            ];
                        }
                    }

                    // Add each paidtime to the $hours array
                    foreach ($intervals as $day => $range) {
                        // Do we already have an entry for this day?
                        if (!key_exists($day, $hours)) {
                            // No data found for this date, create the first
                            $hours[$day][1] = $range[0];
                        } else {
                            // Data found for this date, check if this should be merged
                            $merge = false;
                            $merge_key = null;
                            foreach ($hours[$day] as $key => $value) {
                                // If the existing end is same as this start, we should merge
                                if ($value['end'] == localDateTimeString($paidtime->from)) {
                                    $merge = true;
                                    $merge_key = $key;
                                    break;
                                }
                            }

                            // Merge if needed
                            if ($merge) {
                                $hours[$day][$merge_key]['end'] = $range[0]['end'];
                            } else {
                                $new_key = count($hours[$day]) + 1;
                                $hours[$day][$new_key] = $range[0];
                            }
                        }
                    }
                    print_r($hours);
                }
                break;
            case 'timepunches':
                $data = $this->timepunches()->from($include_from)->to(localDateString($to))->orderBy('in')->direction('asc')->getAll();

                foreach ($data ?? [] as $timepunch) {
                    $intervals = [];

                    if ($ignore_bd) {
                        // Check if Start Date and End Date is different
                        if (localDateString($timepunch->in) !== localDateString($timepunch->out)) {
                            // Tos ending at midnight is converted to 24:00:00 the day before
                            if (localTimeString($timepunch->out) == '00:00:00') {
                                $date = date_create(localDateString($timepunch->out))->modify('-1 days')->format('Y-m-d');
                                $end = '24:00:00';
                                $intervals[$date][0] = ['start' => localTimeString($timepunch->in), 'end' => $end];
                                break;
                            } else // Range passes midnight, and will be split up
                            {
                                // The day the work started
                                $start_date = localDateString($timepunch->in);
                                $start_date_start_time = localTimeString($timepunch->in);
                                $start_date_end_time = '24:00:00';

                                // The day the work ended
                                $end_date = localDateString($timepunch->out);
                                $end_date_start_time = '00:00:00';
                                $end_date_end_time = localTimeString($timepunch->out);

                                // Add both days to the $intervals array
                                $intervals[$start_date][0] = [
                                    'start' => $start_date_start_time,
                                    'end' => $start_date_end_time
                                ];
                                $intervals[$end_date][0] = [
                                    'start' => $end_date_start_time,
                                    'end' => $end_date_end_time
                                ];
                            }
                        } else {
                            // All in a day's work
                            $intervals[localDateString($timepunch->in)][0] = [
                                'start' => localTimeString($timepunch->in),
                                'end' => localTimeString($timepunch->out)
                            ];
                        }

                        // Add each interval to the $hours array
                        foreach ($intervals as $day => $range) {
                            // Do we already have an entry for this day?
                            if (!key_exists($day, $hours)) {
                                // No data found for this date, create the first
                                $hours[$day][1] = $range[0];
                            } else {
                                // Data found for this date, check if this should be merged
                                $merge = false;
                                $merge_key = null;
                                foreach ($hours[$day] as $key => $value) {
                                    // If the existing end is same as this start, we should merge
                                    if ($value['end'] == localTimeString($timepunch->in)) {
                                        $merge = true;
                                        $merge_key = $key;
                                        break;
                                    }
                                }

                                // Merge if needed
                                if ($merge) {
                                    $hours[$day][$merge_key]['end'] = $range[0]['end'];
                                } else {
                                    $new_key = count($hours[$day]) + 1;
                                    $hours[$day][$new_key] = $range[0];
                                }
                            }
                        }
                    } else // Let BusinessDate decide when an employee was working
                    {
                        $businessdate = localDateString($timepunch->business_date);

                        if (key_exists($businessdate, $intervals)) {
                            $intervals[$businessdate][count($intervals[$businessdate])] = [
                                'start' => localDateTimeString($timepunch->in),
                                'end' => localDateTimeString($timepunch->out)
                            ];
                        } else {
                            $intervals[$businessdate][0] = [
                                'start' => localDateTimeString($timepunch->in),
                                'end' => localDateTimeString($timepunch->out)
                            ];
                        }

                        // Add each interval to the $hours array
                        foreach ($intervals as $day => $range) {
                            // Do we already have an entry for this day?
                            if (!key_exists($day, $hours)) {
                                // No data found for this date, create the first
                                $hours[$day][1] = $range[0];
                            } else {
                                // Data found for this date, check if this should be merged
                                $merge = false;
                                $merge_key = null;
                                foreach ($hours[$day] as $key => $value) {
                                    // If the existing end is same as this start, we should merge
                                    if ($value['end'] == localDateTimeString($timepunch->in)) {
                                        $merge = true;
                                        $merge_key = $key;
                                        break;
                                    }
                                }

                                // Merge if needed
                                if ($merge) {
                                    $hours[$day][$merge_key]['end'] = $range[0]['end'];
                                } else {
                                    $new_key = count($hours[$day]) + 1;
                                    $hours[$day][$new_key] = $range[0];
                                }
                            }
                        }
                    }
                }
                break;
            case 'shifts':
                $data = $this->shifts()->from($include_from)->to($to)->orderBy('from')->direction('asc')->getAll();
                break;
            default:
                return null;
        }

        // Again, if we ignore business-dates, we need to exclude the day before, and the day after, if they have hours
        if ($ignore_bd && key_exists($include_from, $hours)) {
            unset($hours[$include_from]);
        }
        if ($ignore_bd && key_exists($exclude_to, $hours)) {
            unset($hours[$exclude_to]);
        }

        // Return array of hours
        return $hours;
    }

    // -----------------------------------------------------------------------------------------------------------------
    public function transferToCustomer(Customer $destination): bool
    {
        try {
            $newEmployee = $destination->copyEmployee($this);
            logg()->info("{lblack}$newEmployee->id[");

            // Paid Times
            logg()->info('p:');
            foreach ($this->paidTimes() as $paidTime) {
                $newEmployee->copyPaidTime($paidTime);
                logg()->info('{dgreen}.');
            }

//            // Notes
//            logg()->info('n:');
//            foreach ($this->notes() as $note) {
//                $newEmployee->copyNote($note);
//                logg()->info('{dgreen}.');
//            }
//
//            // Absences
//            logg()->info('a:');
//            foreach ($this->absences() as $absence) {
//                $newEmployee->copyAbsence($absence);
//                logg()->info('{dgreen}.');
//            }
//
//            // (Un)Availabilities
//            logg()->info('u:');
//            foreach ($this->availabilities() as $availability) {
//                $newEmployee->copyAvailability($availability);
//                logg()->info('{dgreen}.');
//            }
//
//            // FlexiTimes
//            logg()->info('f:');
//            foreach ($this->flexiTimes() as $flexiTime) {
//                $newEmployee->copyFlexiTime($flexiTime);
//                logg()->info('{dgreen}.');
//            }
//
//            // HR Files
//            logg()->info('h:');
//            foreach ($this->hrFiles() as $hrFile) {
//                $newEmployee->copyHrFile($hrFile);
//                logg()->info('{dgreen}.');
//            }
//
//            // Off Times
//            logg()->info('o:');
//            foreach ($this->offTimes() as $offTime) {
//                $newEmployee->copyOffTime($offTime);
//                logg()->info('{dgreen}.');
//            }
//
//            // Positions
//            logg()->info('i:');
//            foreach ($this->positions() as $position) {
//                $newEmployee->copyPosition($position);
//                logg()->info('{dgreen}.');
//            }
//
//            // Qualifications
//            logg()->info('q:');
//            foreach ($this->qualifications() as $qualification) {
//                $newEmployee->copyQualification($qualification);
//                logg()->info('{dgreen}.');
//            }
//
//            // Shifts ?
//            logg()->info('s:');
//            foreach ($this->shifts() as $shift) {
//                $newEmployee->copyShift($shift);
//                logg()->info('{dgreen}.');
//            }
//
//            // Timepunches
//            logg()->info('t:');
//            foreach ($this->timepunches() as $timepunch) {
//                $newEmployee->copyTimepunch($timepunch);
//                logg()->info('{dgreen}.');
//            }
            logg()->info("{lblack}]\n");
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
