<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\Customer;
use Ext\Models\EmployeeContract;

trait HasEmployeeContracts
{
    protected ?array $myContracts = null;

    public function contracts(): array
    {
        return !is_null($this->myContracts)
            ? $this->myContracts
            : $this->myContracts = $this->client->query($this->getFullPath() . '/contracts')->setModel(EmployeeContract::class)->getAll()->all();
    }

    public function findContract(array $attributes = [], string $when = 'now'): ?EmployeeContract
    {
        foreach ($this->contracts() as $contract) {
            if ($contract->matches($attributes)) {
                if (dateTimeWithin($when, $contract->from, $contract->to)) {
                    return $contract;
                }
            }
        }
        return null;
    }

    public function findContracts(array $attributes = [], string $from = null, string $to = null): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($from) && is_null($to)) {
            return $this->contracts();
        }

        $foundContracts = [];
        $start = is_null($from) ? $this->from : $from;
        $end = $to;

        foreach ($this->contracts() as $contract) {
            if ($contract->matches($attributes)) {
                if (intervalWithin($contract->from, $contract->to, $start, $end)) {
                    $foundContracts[] = $contract;
                }
            }
        }
        return $foundContracts;
    }

    public function getContract(string $key): ?EmployeeContract
    {
        return $this->findContract(['id' => $key]);
    }

    public function hasContract(array $attributes = [], string $when = 'now'): bool
    {
        return (bool)$this->findContract($attributes, $when);
    }

    /**
     * @throws Exception
     */
    public function copyContract(EmployeeContract $source, bool $abortOnError = true): ?EmployeeContract
    {
        $sourceType = Customer::get($source->getIdOf('customer'))->settingGroup()->getContractType($source->type_id);
        $destinationType = $this->employer()->settingGroup()->getContractType($sourceType->name, 'name');

        // Abort if no matching ContractType is found
        if ($abortOnError && is_null($destinationType)) {
            throw new Exception('Unknown Contract Type ID');
        }

        try {
            $contract = EmployeeContract::newInstance(array_filter([
                'amount' => $source->amount,
                'amount_type' => $source->amount_type,
                'title' => $source->title,
                'from' => $source->from,
                'to' => $source->to,
                'type_id' => $destinationType?->id
            ], static function ($var) {
                return $var !== null;
            }
            ))->setPath("/customers/$this->customer_id/employees/$this->id/contracts");

            $contract->save();

            return $contract;
        } catch (Exception $e) {
            return null;
        }
    }

    public function newContract(int $typeId, string $amount, string $amountType, string $from, string $to = null, string $title = null): ?EmployeeContract
    {
        // Abort if AmountType is not valid
        if (!in_array($amountType, array('month', 'week', 'year', 'percent'))) {
            throw new Exception('Invalid Amount Type.');
        }

        // Abort if no matching ContractType is found
        if (!$this->employer()->settingGroup()->getContractType($typeId)) {
            throw new Exception('Unknown Contract Type ID');
        }

        try {
            $contract = EmployeeContract::newInstance(array_filter([
                'amount' => $amount,
                'amount_type' => $amountType,
                'title' => $title,
                'from' => $from,
                'to' => $to,
                'type_id' => $typeId
            ], static function ($var) {
                return $var !== null;
            }
            ))->setPath("/customers/$this->customer_id/employees/$this->id/contracts");

            $contract->save();

            return $contract;
        } catch (Exception $e) {
            return null;
        }
    }
}