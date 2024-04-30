<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete: TODO: Complete the list of resolvableSettings
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                     PRIMARY KEY     OK
 * @property mixed|null $name
 * @property mixed|null $description
 * @property mixed|null $class
 */
class Calculator extends Model
{
    protected $path = '/calculators';

    protected array $norwayBaseCalculator = array(
        [
            'key' => 'payroll.holiday_eves',
            'default' => '["norway\/christmas-eve","norway\/new-year-eve","norway\/easter-saturday","norway\/pentecost-eve"]'
        ],
        [
            'key' => 'payroll.eve_start_offset',
            'default' => 15 * 3600
        ],
    );

    /**
     * Static function to get Calculator Model from ID
     *
     * @param int $calculatorId
     *
     * @return \Ext\Models\Calculator|null
     * @author Torbjørn Kallstad
     */
    public static function get(int $calculatorId): ?Calculator
    {
        foreach (Calculator::getAll()->all() as $calculator) {
            if ($calculator->id == $calculatorId) {
                return $calculator;
            }
        }
        return null;
    }

    /**
     * Gets a list of all resolvable settings (and respective default values) associated with this Calculator
     *
     * @return array
     * @author Torbjørn Kallstad
     */
    public function resolvableSettings(): array
    {
        $settings = [];

        switch ($this->id) {
            case 1:     // Modules\Payroll\Calculators\Norway\WorkedHours
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'worked_hours.only_from', 'default' => null],
                    ['key' => 'worked_hours.only_to', 'default' => null],
                    ['key' => 'worked_hours.only_days', 'default' => '[]'],
                    ['key' => 'worked_hours.only_months', 'default' => '[]'],
                ));
                break;
            case 2:     // Modules\Payroll\Calculators\Norway\Restaurants\EveningCompensation_2017
                $settings = $this->norwayBaseCalculator;
                break;
            case 3:     // Modules\Payroll\Calculators\Norway\WorkedDays
                $settings = $this->norwayBaseCalculator;
                break;
            case 4:     // Modules\Payroll\Calculators\Norway\Restaurants\HolidayCompensation_2017
                $settings = $this->norwayBaseCalculator;
                break;
            case 5:     // Modules\Payroll\Calculators\Norway\Restaurants\NightCompensation_2017
                $settings = $this->norwayBaseCalculator;
                break;
            case 6:     // Modules\Payroll\Calculators\Norway\Restaurants\WeekendCompensation_2017
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'weekend_compensation.start_time_saturday', 'default' => '14:00'],
                    ['key' => 'weekend_compensation.end_time_saturday', 'default' => '24:00'],
                    ['key' => 'weekend_compensation.start_time_sunday', 'default' => '06:00'],
                    ['key' => 'weekend_compensation.end_time_sunday', 'default' => '24:00'],
                ));
                break;
            case 9:     // Modules\Payroll\Calculators\Norway\Kompis\SyketimeCalculator
                $settings = $this->norwayBaseCalculator;
                break;
            case 10:    // Modules\Payroll\Calculators\SalaryCalculator
                break;
            case 11:    // Modules\Payroll\Calculators\Norway\Retail\EveningCompensation1
                $settings = $this->norwayBaseCalculator;
                break;
            case 12:    // Modules\Payroll\Calculators\Norway\Retail\EveningCompensation2
                $settings = $this->norwayBaseCalculator;
                break;
            case 13:    // Modules\Payroll\Calculators\Norway\Retail\SaturdayCompensation1
                $settings = $this->norwayBaseCalculator;
                break;
            case 15:    // Modules\Payroll\Calculators\Norway\Retail\SaturdayCompensation2
                $settings = $this->norwayBaseCalculator;
                break;
            case 16:    // Modules\Payroll\Calculators\Norway\WeekOvertimeCalculator
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'payroll.overtime_week_limit', 'default' => 40 * 3600],
                    ['key' => 'payroll.overtime_day_limit', 'default' => 9 * 3600],
                    ['key' => 'overtime_counter.start_date', 'default' => '2021-01-04'],
                ));
                break;
            case 17:    // Modules\Payroll\Calculators\Norway\DayOvertimeCalculator
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'payroll.overtime_day_limit', 'default' => 9 * 3600],
                ));
                break;
            case 18:    // Modules\Payroll\Calculators\Norway\GasStations\EveningCompensation1Calculator
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'gas_station_addon.threshold', 'default' => 12],
                ));
                break;
            case 19:    // Modules\Payroll\Calculators\Norway\GasStations\EveningCompensation2Calculator
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'gas_station_addon.threshold', 'default' => 12],
                ));
                break;
            case 20:    // Modules\Payroll\Calculators\Norway\GasStations\WeekNightCalculator
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'gas_station_addon.threshold', 'default' => 12],
                ));
                break;
            case 21:    // Modules\Payroll\Calculators\Norway\GasStations\SaturdayEveningCalculator
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'gas_station_addon.threshold', 'default' => 12],
                ));
                break;
            case 22:    // Modules\Payroll\Calculators\Norway\GasStations\SundayNightCalculator
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'gas_station_addon.threshold', 'default' => 12],
                ));
                break;
            case 23:    // Modules\Payroll\Calculators\Norway\GasStations\SundayDayCalculator
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'gas_station_addon.threshold', 'default' => 12],
                ));
                break;
            case 24:    // Modules\Payroll\Calculators\Norway\GasStations\SundayEveningCalculator
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'gas_station_addon.threshold', 'default' => 12],
                ));
                break;
            case 25:    // Modules\Payroll\Calculators\Norway\Restaurants\BareMinimumHolidayCompensation
                $settings = $this->norwayBaseCalculator;
                break;
            case 26:    // Modules\Payroll\Calculators\MealDeductionCalculator
                $settings = $this->norwayBaseCalculator;
                break;
            case 27:    // \Modules\Payroll\Calculators\ShiftCostCalculator
                break;
            case 29:    // Modules\Payroll\Calculators\SimpleExternalEmployeeCostCalculator
                break;
            case 30:    // Modules\Payroll\Calculators\Norway\Retail\HolidayCompensation_2019
                $settings = array(
                    [
                        'key' => 'payroll.holiday_eves',
                        'default' => '["norway\/christmas-eve","norway\/new-year-eve","norway\/pentecost-eve"]'
                    ], // [ 'norway/pentecost-eve', 'norway/christmas-eve', 'norway/new-year-eve' ] ],
                    ['key' => 'payroll.eve_start_offset', 'default' => 15 * 3600],
                );
                break;
            case 31:    // \Modules\Payroll\Calculators\Norway\Pirbadet\EveningCompensation
                $settings = $this->norwayBaseCalculator;
                break;
            case 33:    // Modules\Payroll\Calculators\Norway\Pirbadet\HolidayCompensation_2019
                $settings = array(
                    [
                        'key' => 'payroll.holiday_eves',
                        'default' => '["norway\/christmas-eve","norway\/new-year-eve","norway\/pentecost-eve"]'
                    ], // [ 'norway/pentecost-eve', 'norway/christmas-eve', 'norway/new-year-eve' ] ],
                    ['key' => 'payroll.eve_start_offset', 'default' => 15 * 3600],
                );
                break;
            case 34:    // Modules\Payroll\Calculators\ShiftCostCalculator
                break;
            case 37:    // Modules\Payroll\Calculators\Norway\Pirbadet\OvertimeCompensation50
                $settings = $this->norwayBaseCalculator;
                break;
            case 38:    // Modules\Payroll\Calculators\Norway\Pirbadet\OvertimeCompensation100
                $settings = $this->norwayBaseCalculator;
                break;
            case 39:    // Modules\Payroll\Calculators\Norway\Restaurants\McDonalds\HolidayCompensation_2019
                $settings = $this->norwayBaseCalculator;
                break;
            case 40:    // TODO: Modules\Payroll\Calculators\Sweden\Retail\HolidayCompensation_2019
                break;
            case 41:    // Modules\Payroll\Calculators\Norway\Retail\FredrikOgLouisa\OvertimeCompensation50
                $settings = $this->norwayBaseCalculator;
                break;
            case 42:    // Modules\Payroll\Calculators\Norway\Retail\FredrikOgLouisa\OvertimeCompensation100
                $settings = $this->norwayBaseCalculator;
                break;
            case 43:    // Modules\Payroll\Calculators\Norway\Retail\FredrikOgLouisa\AveragedOvertimeCompensation
                $settings = $this->norwayBaseCalculator;
                break;
            case 44:    // Modules\Payroll\Calculators\Norway\Restaurants\McDonalds\NightCompensation_2019
                $settings = $this->norwayBaseCalculator;
                break;
            case 45:    // Modules\Payroll\Calculators\Norway\Pirbadet\WeekendCompensation_2019
                $settings = array(
                    [
                        'key' => 'payroll.holiday_eves',
                        'default' => '["norway\/christmas-eve","norway\/new-year-eve","norway\/pentecost-eve"]'
                    ], // [ 'norway/pentecost-eve', 'norway/christmas-eve', 'norway/new-year-eve' ] ],
                    ['key' => 'payroll.eve_start_offset', 'default' => 12 * 3600],
                    ['key' => 'weekend_compensation.start_time_saturday', 'default' => '06:00'],
                    ['key' => 'weekend_compensation.end_time_saturday', 'default' => '24:00'],
                    ['key' => 'weekend_compensation.start_time_sunday', 'default' => '06:00'],
                    ['key' => 'weekend_compensation.end_time_sunday', 'default' => '24:00'],
                );
                break;
            case 46:    // Modules\LeaveShifts\Calculators\LeaveShiftCalculator
                break;
            case 47:    // Modules\Payroll\Calculators\Norway\Retail\ConfigurableRetailAddonCalculator
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'retail_addon.threshold', 'default' => 12],
                    ['key' => 'retail_addon.from', 'default' => '00:00'],
                    ['key' => 'retail_addon.to', 'default' => '24:00'],
                    ['key' => 'retail_addon.only_days', 'default' => '[]'],
                ));
                break;
            case 48:    // TODO: Modules\Payroll\Calculators\Finland\DayOvertimeCalculator1
                break;
            case 49:    // TODO: Modules\Payroll\Calculators\Finland\DayOvertimeCalculator2
                break;
            case 50:    // TODO: Modules\Payroll\Calculators\Finland\WeekOvertimeCalculator
                break;
            case 51:    // TODO: Modules\Payroll\Calculators\USA\DayOvertimeCalculator
                break;
            case 52:    // TODO: Modules\Payroll\Calculators\USA\WeekOvertimeCalculator
                break;
            case 53:    // Modules\Payroll\Calculators\HolidayCalculator
                $settings = array(
                    ['key' => 'payroll.holiday_eves', 'default' => '[]'],
                    ['key' => 'payroll.excepted_holidays', 'default' => '[]'],
                    ['key' => 'payroll.only_holidays', 'default' => '[]'],
                    ['key' => 'payroll.eve_start_offset', 'default' => 15 * 3600],
                    ['key' => 'payroll.eve_end_offset', 'default' => 24 * 3600],
                    ['key' => 'payroll.holiday_start_offset', 'default' => 0],
                    ['key' => 'payroll.holiday_end_offset', 'default' => 24 * 3600],
                );
                break;
            case 54:    // Modules\Payroll\Calculators\AbsenceDayCalculator
                $settings = array(
                    ['key' => 'absence_codes', 'default' => '[]'],
                );
                break;
            case 55:    // TODO: Modules\Payroll\Calculators\Finland\Restaurants\WeekendCompensation_2020
                break;
            case 56:    // Modules\Payroll\Calculators\Norway\AveragedWeekOvertimeCalculator
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'overtime.average_over_weeks', 'default' => 6],
                    ['key' => 'overtime.day_limit', 'default' => 9 * 3600],
                    ['key' => 'overtime.week_limit', 'default' => 50 * 3600],
                    ['key' => 'overtime.average_week_limit', 'default' => 38 * 3600],
                    ['key' => 'overtime.start_time', 'default' => '00:00'],
                    ['key' => 'overtime.end_time', 'default' => '24:00'],
                    ['key' => 'overtime.only_days', 'default' => '[]'],
                    ['key' => 'overtime.exclude_day_before_holiday', 'default' => 'false'],
                    ['key' => 'overtime.only_day_before_holiday', 'default' => 'false'],
                    ['key' => 'overtime.only_holidays', 'default' => '[]'],
                    ['key' => 'overtime.except_holidays', 'default' => '[]'],
                    ['key' => 'overtime.deduct_outside_contract', 'default' => 'false'],
                    ['key' => 'overtime.work_days', 'default' => '[1,2,3,4,5]'],
                    ['key' => 'overtime.include_leave_shifts', 'default' => 'false'],
                    ['key' => 'overtime_counter.start_date', 'default' => 24 * 3600],
                    ['key' => 'overtime.flex_basis', 'default' => null],
                ));
                break;
            case 58:    // Modules\Payroll\Calculators\Norway\ConfigurableDayOvertimeCalculator
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'payroll.overtime_day_limit', 'default' => 9 * 3600],
                    ['key' => 'overtime.start_time', 'default' => '00:00'],
                    ['key' => 'overtime.end_time', 'default' => '24:00'],
                    ['key' => 'overtime.only_days', 'default' => '[]'],
                    ['key' => 'overtime.only_months', 'default' => '[]'],
                    ['key' => 'overtime.exclude_day_before_holiday', 'default' => 'false'],
                    ['key' => 'overtime.only_day_before_holiday', 'default' => 'false'],
                    ['key' => 'overtime.only_holidays', 'default' => null],
                    ['key' => 'overtime.except_holidays', 'default' => null],
                    ['key' => 'overtime.flex_basis', 'default' => null],
                    ['key' => 'overtime.include_leave_shifts', 'default' => '1'],
                ));
                break;
            case 59:    // Modules\LeaveShifts\Calculators\LeaveShiftLengthCalculator
                break;
            case 60:    // Modules\Payroll\Calculators\ConfigurableAddonCalculator
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'addon.start_time', 'default' => '00:00'],
                    // OK
                    ['key' => 'addon.end_time', 'default' => '24:00'],
                    // OK
                    ['key' => 'addon.only_days', 'default' => '[]'],
                    // OK
                    ['key' => 'payroll.overtime_day_limit', 'default' => 9 * 3600],
                    //
                    ['key' => 'overtime.week_limit', 'default' => 50 * 3600],
                    //
                    ['key' => 'addon.exclude_day_before_holiday', 'default' => 'false'],
                    //
                    ['key' => 'addon.only_day_before_holiday', 'default' => 'false'],
                    //
                    ['key' => 'addon.only_holidays', 'default' => null],
                    //
                    ['key' => 'addon.except_holidays', 'default' => null],
                    //
                    ['key' => 'addon.include_leave_shifts', 'default' => 'true'],
                    //
                    ['key' => 'overtime_counter.start_date', 'default' => '2021-01-04'],
                    //
                ));
                break;
            case 61:    // TODO: Modules\Payroll\Calculators\Switzerland\NightHoursCalculator
                break;
            case 62:    // Modules\Payroll\Calculators\VacationDayCalculator
                $settings = array(
                    ['key' => 'absence_codes', 'default' => '[]'],
                );
                break;
            case 63:    // Modules\Payroll\Calculators\Norway\QuarterRetailAddonCalculator
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'retail_addon.threshold', 'default' => 12],
                    ['key' => 'retail_addon.from', 'default' => '00:00'],
                    ['key' => 'retail_addon.to', 'default' => '24:00'],
                    ['key' => 'retail_addon.only_days', 'default' => '[]'],
                    ['key' => 'payroll.overtime_day_limit', 'default' => 10 * 3600],
                    ['key' => 'payroll.overtime_week_limit', 'default' => 50 * 3600],
                    ['key' => 'payroll.overtime_quarter_average_week_limit', 'default' => 38 * 3600],
                    ['key' => 'retail_addon.include_leave_shifts', 'default' => '0'],
                    ['key' => 'retail_addon.only_months', 'default' => '[]'],
                ));
                break;
            case 64:    // Modules\Payroll\Calculators\Norway\QuarterOvertimeCalculator
                $settings = array(
                    ['key' => 'overtime.day_limit', 'default' => 9 * 3600],
                    ['key' => 'overtime.week_limit', 'default' => 50 * 3600],
                    ['key' => 'overtime.quarter_average_week_limit', 'default' => 38 * 3600],
                    ['key' => 'overtime.include_leave_shifts', 'default' => 'false'],
                    ['key' => 'overtime.start_time', 'default' => '00:00'],
                    ['key' => 'overtime.end_time', 'default' => '24:00'],
                    ['key' => 'overtime.only_days', 'default' => '[]'],
                );
                break;
            case 65:    // Modules\Payroll\Calculators\WeeksWorkedCalculator
                break;
            case 66:    // Modules\Payroll\Calculators\Norway\WeeklyUniformDeductionCalculator
                break;
            case 67:    // Modules\Payroll\Calculators\Norway\QuarterOvertimeWithAverageWeekLimitCalculator
                $settings = array(
                    ['key' => 'overtime.day_limit', 'default' => 9 * 3600],
                    ['key' => 'overtime.week_limit', 'default' => 50 * 3600],
                    ['key' => 'overtime.quarter_average_week_limit', 'default' => 38 * 3600],
                    ['key' => 'overtime.include_leave_shifts', 'default' => 'false'],
                    ['key' => 'overtime.start_time', 'default' => '00:00'],
                    ['key' => 'overtime.end_time', 'default' => '24:00'],
                    ['key' => 'overtime.only_days', 'default' => '[]'],
                    ['key' => 'overtime.average_week_limit', 'default' => 38 * 3600],
                    ['key' => 'overtime_counter.start_date', 'default' => '2021-01-04'],
                    ['key' => 'overtime.average_over_weeks', 'default' => 6],
                );
                break;
            case 68:    // Modules\Payroll\Calculators\Norway\LongAveragedWeekOvertimeCalculator
                $settings = array_merge($this->norwayBaseCalculator, array(
                    ['key' => 'overtime.day_limit', 'default' => 9 * 3600],
                    ['key' => 'overtime.week_limit', 'default' => 50 * 3600],
                    ['key' => 'overtime.average_over_weeks', 'default' => 6],
                    ['key' => 'overtime.average_over_weeks_2', 'default' => 52],
                    ['key' => 'overtime.average_week_limit', 'default' => 38 * 3600],
                    ['key' => 'overtime.average_week_limit_2', 'default' => 38 * 3600],
                    ['key' => 'overtime.start_time', 'default' => '00:00'],
                    ['key' => 'overtime.end_time', 'default' => '24:00'],
                    ['key' => 'overtime.only_days', 'default' => '[]'],
                    ['key' => 'overtime.exclude_day_before_holiday', 'default' => 'false'],
                    ['key' => 'overtime.only_day_before_holiday', 'default' => 'false'],
                    ['key' => 'overtime.only_holidays', 'default' => '[]'],
                    ['key' => 'overtime.except_holidays', 'default' => '[]'],
                    ['key' => 'overtime.deduct_outside_contract', 'default' => 'false'],
                    ['key' => 'overtime.include_leave_shifts', 'default' => 'false'],
                    ['key' => 'overtime_counter.start_date', 'default' => '2021-01-04'],
                ));
                break;
            default:
                return [];
        }
        return $settings;
    }
}
