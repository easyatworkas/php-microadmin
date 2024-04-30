<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\Customer;
use Ext\Models\EmployeePayRate;

trait HasEmployeePayRates
{
    protected ?array $myPayRates = null;

    public function payRates(): array
    {
        return !is_null($this->myPayRates)
            ? $this->myPayRates
            : $this->myPayRates = $this->client->query($this->getFullPath() . '/pay_rates')->setModel(EmployeePayRate::class)->getAll()->all();
    }

    public function findPayRate(array $attributes = [], string $when = 'now'): ?EmployeePayRate
    {
        foreach ($this->payRates() as $payRate) {
            if ($payRate->matches($attributes)) {
                if (dateTimeWithin($when, $payRate->from, $payRate->to)) {
                    return $payRate;
                }
            }
        }
        return null;
    }

    public function findPayRates(array $attributes = [], string $from = null, string $to = null): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($from) && is_null($to)) {
            return $this->payRates();
        }

        $foundPayRates = [];
        $start = is_null($from) ? $this->from : $from;
        $end = $to;

        foreach ($this->payRates() as $payRate) {
            if ($payRate->matches($attributes)) {
                if (intervalWithin($payRate->from, $payRate->to, $start, $end)) {
                    $foundPayRates[] = $payRate;
                }
            }
        }
        return $foundPayRates;
    }

    public function getPayRate(string $key): ?EmployeePayRate
    {
        return $this->findPayRate(['id' => $key]);
    }

    public function hasPayRate(array $attributes = [], string $when = 'now'): bool
    {
        return (bool)$this->findPayRate($attributes, $when);
    }

    /**
     * @throws Exception
     */
    public function copyPayRate(EmployeePayRate $source): ?EmployeePayRate
    {
        if ($source->tariff_id) {
            $sourceTariff = Customer::get($source->getIdOf('customer'))->settingGroup()->getTariff($source->tariff_id);
            $destinationTariff = $this->employer()->settingGroup()->getTariff($sourceTariff->name, 'name');

            // Abort if no matching Tariff is found
            if (is_null($destinationTariff)) {
                throw new Exception('Unknown Tariff ID');
            }
        }

        try {
            $payRate = EmployeePayRate::newInstance(array_filter([
                'from' => $source->from,
                'to' => $source->to,
                'rate' => $source->rate,
                'type' => $source->type,
                'tariff_id' => $source->tariff_id
            ], static function ($var) {
                return $var !== null;
            }
            ))->setPath("/customers/$this->customer_id/employees/$this->id/pay_rates");

            $payRate->save();

            return $payRate;
        } catch (Exception $e) {
            return null;
        }
    }

    public function newPayRate(string $rate, string $type, string $from, string $to = null, int $tariffId = null): ?EmployeePayRate
    {
        // Abort if Rate Type is not valid
        if (!in_array($type, array('hour', 'day', 'week', 'month', 'year'))) {
            throw new Exception('Invalid Rate Type.');
        }

        // Abort if no matching Tariff is found
        if ($tariffId && !$this->employer()->settingGroup()->hasTariffWith(['id' => $tariffId])) {
            throw new Exception('Unknown Tariff ID');
        }

        try {
            $payRate = EmployeePayRate::newInstance(array_filter([
                'rate' => $rate,
                'type' => $type,
                'from' => $from,
                'to' => $to,
                'tariff_id' => $tariffId
            ], static function ($var) {
                return $var !== null;
            }
            ))->setPath("/customers/$this->customer_id/employees/$this->id/pay_rates");

            $payRate->save();

            return $payRate;
        } catch (Exception $e) {
            return null;
        }
    }
}