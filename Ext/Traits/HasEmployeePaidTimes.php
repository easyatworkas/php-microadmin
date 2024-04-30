<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateInterval;
use Ext\PeriodDefinition;
use League\Period\Chart\ConsoleOutput;
use League\Period\Chart\Output;
use League\Period\Period;
use League\Period\Sequence;
use Exception;
use Ext\Models\EmployeePaidTime;

trait HasEmployeePaidTimes
{
    protected ?array $myPaidTimes = null;

    public function paidTimes(): array
    {
        return !is_null($this->myPaidTimes)
            ? $this->myPaidTimes
            : $this->myPaidTimes = $this->client->query($this->getFullPath() . '/paid_times')->setModel(EmployeePaidTime::class)
                ->from($this->from)
                ->to('now')
                ->getAll()->all();
    }

    public function findPaidTime(array $attributes = [], string $when = 'now'): ?EmployeePaidTime
    {
        foreach ($this->paidTimes() as $paidTime) {
            if ($paidTime->matches($attributes)) {
                if (dateTimeWithin($when, $paidTime->from, $paidTime->to)) {
                    return $paidTime;
                }
            }
        }
        return null;
    }

    public function findPaidTimes(array $attributes = [], string $from = null, string $to = null, bool $strict = true, bool $useBd = false): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($from) && is_null($to)) {
            return $this->paidTimes();
        }

        $foundPaidTimes = [];
        $start = is_null($from) ? $this->from : $from;
        $end = $to;

        foreach ($this->paidTimes() as $paidTime) {
            if ($paidTime->matches($attributes)) {
                if ($useBd) {
                    if (bdWithin($paidTime->business_date, $start, $end)) {
                        $foundPaidTimes[] = $paidTime;
                    }
                } else {
                    if (intervalWithin($paidTime->from, $paidTime->to, $start, $end, $strict)) {
                        $foundPaidTimes[] = $paidTime;
                    }
                }
            }
        }
        return $foundPaidTimes;
    }

    public function getPaidTime(string $key): ?EmployeePaidTime
    {
        return $this->findPaidTime(['id' => $key]);
    }

    public function hasPaidTime(array $attributes = [], string $when = 'now'): bool
    {
        return (bool)$this->findPaidTime($attributes, $when);
    }

    public function copyPaidTime(EmployeePaidTime $source): ?EmployeePaidTime
    {
        return $this->newPaidTime($source->from, $source->to, $source->business_date, true);
    }

    public function newPaidTime(string $from, string $to, string $businessDate = null, bool $forceUpdate = false): ?EmployeePaidTime
    {
        try {
            if ($forceUpdate) // This will force update the paidTime in case an observer messes with it (creates a break in the middle, etc.)
            {
                $newTo = date_create($from)->add(DateInterval::createFromDateString('1 second'))->format('Y-m-d H:i:s');

                $paidTime = EmployeePaidTime::newInstance(array_filter([
                    'from' => $from,
                    'to' => $newTo,
                    'business_date' => $businessDate,
                ], static function ($var) {
                    return $var !== null;
                }
                ))->setPath("/customers/$this->customer_id/employees/$this->id/paid_times");

                $paidTime->save();

                $paidTime->update(['from' => $from, 'to' => $to, 'business_date' => $businessDate]);
            }
            else
            {
                $paidTime = EmployeePaidTime::newInstance(array_filter([
                    'from' => $from,
                    'to' => $to,
                    'business_date' => $businessDate,
                ], static function ($var) {
                    return $var !== null;
                }
                ))->setPath("/customers/$this->customer_id/employees/$this->id/paid_times");

                $paidTime->save();
            }

            return $paidTime;
        } catch (Exception $e) {
            echo "ERROR: ".$e->getCode().' '.$e->getMessage();
            return null;
        }
    }

    public function paidTimeHours(string $localFrom, string $localTo, bool $useBusinessDates = true): float
    {
        $utcFrom = \Carbon\Carbon::parse($localFrom, $this->employer()->time_zone)->setTimezone('UTC');
        $utcTo = \Carbon\Carbon::parse($localTo, $this->employer()->time_zone)->setTimezone('UTC');

        $paidTimes = $this->findPaidTimes([],
            $utcFrom->format('Y-m-d H:i:s'),
            $utcTo->format('Y-m-d H:i:s'),
            false, $useBusinessDates);

        if ($useBusinessDates)
        {
            $sequence = new \League\Period\Sequence();

            foreach ($paidTimes as $paidTime) {
                $sequence->push(\League\Period\Period::fromDatepoint(
                    \Carbon\Carbon::parse($paidTime->from, 'UTC'),
                    \Carbon\Carbon::parse($paidTime->to, 'UTC')));
            }

            return $sequence->totalTimeDuration() / 3600;
        }
        else
        {
            $requestedPeriod = \League\Period\Period::fromDatepoint($utcFrom, $utcTo);

            $sequence = new \League\Period\Sequence($requestedPeriod);

            foreach ($paidTimes as $paidTime) {
                $sequence->push(\League\Period\Period::fromDatepoint(
                    \Carbon\Carbon::parse($paidTime->from, 'UTC'),
                    \Carbon\Carbon::parse($paidTime->to, 'UTC')));
            }

            return $sequence->intersections()->totalTimeDuration() / 3600;
        }
    }

    public function paidTimeSequence(string $localFrom, string $localTo, bool $useBusinessDates = true): \League\Period\Sequence
    {
        $utcFrom = \Carbon\Carbon::parse($localFrom, $this->employer()->time_zone)->setTimezone('UTC');
        $utcTo = \Carbon\Carbon::parse($localTo, $this->employer()->time_zone)->setTimezone('UTC');

        $paidTimes = $this->findPaidTimes([],
            $utcFrom->format('Y-m-d H:i:s'),
            $utcTo->format('Y-m-d H:i:s'),
            false, $useBusinessDates);

        $sequence = $useBusinessDates ? new \League\Period\Sequence() : new \League\Period\Sequence(\League\Period\Period::fromDatepoint($utcFrom, $utcTo));

        foreach ($paidTimes as $paidTime) {
            $sequence->push(\League\Period\Period::fromDatepoint(
                \Carbon\Carbon::parse($paidTime->from, 'UTC'),
                \Carbon\Carbon::parse($paidTime->to, 'UTC')));
        }

        return $useBusinessDates ? $sequence : $sequence->intersections();
    }

    public function finalHours(
        string $localFrom,
        string $localTo,
        array $includedDefinitions,
        array $excludedDefinitions,
        bool $useBusinessDates = true): array
    {
        $result = [];
        $includedHours = 0;
        $excludedHours = 0;

        // Get PaidTime Periods
        $paidTimes = $this->paidTimeSequence($localFrom, $localTo, $useBusinessDates);

        // Calculate All Included Time
        $inclCount = 1;
        foreach ($includedDefinitions as $definition)
        {
            if ($definition::class !== \Ext\PeriodDefinition::class) {
                $result['incl.'.$inclCount] = 'N/A';
                $inclCount += 1;
                continue;
            }

            // Get Included Periods
            $included = $definition->sequence();

            // Get PaidTime Intersections with Included
            foreach ($paidTimes as $interval) {
                $included->push($interval);
            }

            $includedHours += $included->intersections()->totalTimeDuration() / 3600;
            $result['incl.'.$inclCount] = $included->intersections()->totalTimeDuration() / 3600;

            $inclCount += 1;
        }

        // Calculate All Excluded Time
        $exclCountCount = 1;
        foreach ($excludedDefinitions as $definition)
        {
            if ($definition::class !== \Ext\PeriodDefinition::class) {
                $result['excl.'.$exclCountCount] = 'N/A';
                $exclCountCount += 1;
                continue;
            }

            // Get Excluded Periods
            $excluded = $definition->sequence();

            // Get PaidTime Intersections with Included and Excluded
            foreach ($paidTimes as $interval) {
                $excluded->push($interval);
            }

            $excludedHours += $excluded->intersections()->totalTimeDuration() / 3600;
            $result['excl.'.$exclCountCount] = $excluded->intersections()->totalTimeDuration() / 3600;

            $exclCountCount += 1;
        }

        $result['result'] = $includedHours - $excludedHours;

        return $result;
    }

    public function ganttChart3(
        string $localFrom,
        string $localTo,
        array $include = [],
        array $exclude = [],
        bool $useBusinessDates = true): array
    {
        $timeZone = $this->employer()->time_zone;

        $dataSet = new \League\Period\Chart\Dataset();

        // Get Included Periods
        $included = periodSequence($localFrom, $localTo, $timeZone, $include['days'], $include['hours']);

        // Get Excluded Periods
        $excluded = periodHolidays($localFrom, $localTo, $exclude['holidays'], $exclude['hours'], $timeZone);

        // Append Requested Periods to the dataset
        $dataSet->append('included', $included);

        // Get PaidTimes within the requested period
        $sequence = new \League\Period\Sequence();
        $combinedIncluded = new \League\Period\Sequence();
        $combinedExcluded = new \League\Period\Sequence();

        $utcFrom = \Carbon\Carbon::parse($localFrom, $timeZone)->setTimezone('UTC');
        $utcTo = \Carbon\Carbon::parse($localTo, $timeZone)->setTimezone('UTC');

        $paidTimes = $this->findPaidTimes([],
            $utcFrom->format('Y-m-d H:i:s'),
            $utcTo->format('Y-m-d H:i:s'),
            false, $useBusinessDates);

        foreach ($paidTimes as $paidTime) {
            $period = \League\Period\Period::fromDatepoint(
                \Carbon\Carbon::parse($paidTime->from, 'UTC')->setTimezone($timeZone),
                \Carbon\Carbon::parse($paidTime->to, 'UTC')->setTimezone($timeZone)
            );
            $sequence->push($period);
            $combinedIncluded->push($period);
            $combinedExcluded->push($period);
        }

        // Append Paid Time sequence to the dataset
        $dataSet->append('paidtimes', $sequence);

        // Append Overlap
        foreach ($included as $interval) {
            $combinedIncluded->push($interval);
        }
        $dataSet->append('included', $combinedIncluded->intersections());

        // Append Overlap
        foreach ($excluded as $interval) {
            $combinedExcluded->push($interval);
        }
        $dataSet->append('excluded', $combinedExcluded->intersections());

//        $config = \League\PeriodDefinition\Chart\GanttChartConfig::createFromRainbow()->withWidth(getScreenWidth() - ($dataSet->labelMaxLength() + 2));
//        (new \League\PeriodDefinition\Chart\GanttChart($config))->stroke($dataSet);

        $result = [];
        $result['included'] = $combinedIncluded->intersections()->totalTimeDuration() / 3600;
        $result['excluded'] = $combinedExcluded->intersections()->totalTimeDuration() / 3600;

        return $result;
    }

    public function ganttChart2(
        string $localFrom,
        string $localTo,
        array $days = [0,1,2,3,4,5,6], // 0=Sunday, 1=Monday, etc.
        array $hours = ['from' => '00:00', 'to' => '24:00'],
        bool $useBusinessDates = true): float
    {
        $timeZone = $this->employer()->time_zone;

        $dataSet = new \League\Period\Chart\Dataset();

        // Get Requested Periods
        $requested = periodSequence($localFrom, $localTo, $timeZone, $days, $hours);

        // Append Requested Periods to the dataset
        $dataSet->append('requested', $requested);

        // Get PaidTimes within the requested period
        $sequence = new \League\Period\Sequence();
        $combined = new \League\Period\Sequence();

        $utcFrom = \Carbon\Carbon::parse($localFrom, $timeZone)->setTimezone('UTC');
        $utcTo = \Carbon\Carbon::parse($localTo, $timeZone)->setTimezone('UTC');
        $paidTimes = $this->findPaidTimes([],
            $utcFrom->format('Y-m-d H:i:s'),
            $utcTo->format('Y-m-d H:i:s'),
            false, $useBusinessDates);

        foreach ($paidTimes as $paidTime) {
            $period = \League\Period\Period::fromDatepoint(
                \Carbon\Carbon::parse($paidTime->from, 'UTC')->setTimezone($timeZone),
                \Carbon\Carbon::parse($paidTime->to, 'UTC')->setTimezone($timeZone)
            );
            $sequence->push($period);
            $combined->push($period);
        }

        // Append Paid Time sequence to the dataset
        $dataSet->append('paidtimes', $sequence);

        // Append Overlap
        foreach ($requested as $interval) {
            $combined->push($interval);
        }
        $dataSet->append('overlap', $combined->intersections());

//        $config = \League\PeriodDefinition\Chart\GanttChartConfig::createFromRainbow()->withWidth(getScreenWidth() - ($dataSet->labelMaxLength() + 2));
//        (new \League\PeriodDefinition\Chart\GanttChart($config))->stroke($dataSet);

        return $combined->intersections()->totalTimeDuration() / 3600;
    }

    public function ganttChart(string $localFrom, string $localTo, bool $useBusinessDates = true): void
    {
        $utcFrom = \Carbon\Carbon::parse($localFrom, $this->employer()->time_zone)->setTimezone('UTC');
        $utcTo = \Carbon\Carbon::parse($localTo, $this->employer()->time_zone)->setTimezone('UTC');

        $paidTimes = $this->findPaidTimes([],
            $utcFrom->format('Y-m-d H:i:s'),
            $utcTo->format('Y-m-d H:i:s'),
            false, $useBusinessDates);

        $requestedPeriod = \League\Period\Period::fromDatepoint($utcFrom, $utcTo);

        $sequence = new \League\Period\Sequence();

        foreach ($paidTimes as $paidTime) {
            $sequence->push(\League\Period\Period::fromDatepoint(
                \Carbon\Carbon::parse($paidTime->from, 'UTC'),
                \Carbon\Carbon::parse($paidTime->to, 'UTC')));
        }

        $dataset = new \League\Period\Chart\Dataset([
            ['total', $requestedPeriod],
            ['found', $sequence],
        ]);

        $config = \League\Period\Chart\GanttChartConfig::createFromRainbow()->withWidth(getScreenWidth() - 7); // 2 + length of longest label

        (new \League\Period\Chart\GanttChart($config))->stroke($dataset);
    }

    public function oldpaidTimeHours(string $utcFrom, string $utcTo, bool $ignoreBusinessDates = true): ?array
    {
        // If we ignore business-date, we need to include the day before, so that hours stretching into the first
        // day that we actually want can be handled, since backend only looks at business-dates when parsing '->from()'
        // This extra day will be removed before result is returned.
        if ($ignoreBusinessDates) {
            $include_from = date_create(localDateString($utcFrom))->modify('-1 days')->format('Y-m-d');
            $exclude_to = date_create(localDateString($utcTo))->modify('+1 days')->format('Y-m-d');
        } else {
            $include_from = localDateString($utcFrom);
            $exclude_to = '';
        }

        $hours = [];
        // -------------------------------------------------------------------------------------------------------------
        // If we ignore business-date, we need to order by 'from' instead of 'business_date'
        if ($ignoreBusinessDates) {
            $data = $this->paidTimes()->from($include_from)->to(localDateString($utcTo))->orderBy('from')->direction('asc')->getAll();
        } else {
            $data = $this->paidTimes()->from($include_from)->to(localDateString($utcTo))->orderBy('business_date')->direction('asc')->getAll();
        }

        foreach ($data ?? [] as $paidtime) {
            $intervals = [];

            if ($ignoreBusinessDates) {
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
                        $intervals[$start_date][0] = ['start' => $start_date_start_time, 'end' => $start_date_end_time];
                        $intervals[$end_date][0] = ['start' => $end_date_start_time, 'end' => $end_date_end_time];
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
        // -------------------------------------------------------------------------------------------------------------

        // Again, if we ignore business-dates, we need to exclude the day before, and the day after, if they have hours
        if ($ignoreBusinessDates) {
            if (key_exists($include_from, $hours)) {
                unset($hours[$include_from]);
            }
            if (key_exists($exclude_to, $hours)) {
                unset($hours[$exclude_to]);
            }
        }

        // Return array of hours
        return $hours;
    }
}