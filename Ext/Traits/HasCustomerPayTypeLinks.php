<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\Customer;
use Ext\Models\CustomerPayTypeLink;
use Ext\Models\SettingGroup;

trait HasCustomerPayTypeLinks
{
    public function activePayTypeLinks(): array
    {
        if (!$this->hasProduct('Payroll')) return [];

        return static::newQuery($this->getFullPath() . '/pay_type_links')->setModel(CustomerPayTypeLink::class)
            ->active(true)
            ->with([ 'contractTypes', 'absenceTypes', 'properties' ])
            ->getAll()
            ->all();
    }

    public function payTypeLinks(): array
    {
        if (!$this->hasProduct('Payroll')) return [];

        return static::newQuery($this->getFullPath() . '/pay_type_links')->setModel(CustomerPayTypeLink::class)
            ->active(false)
            ->with([ 'contractTypes', 'absenceTypes', 'properties' ])
            ->getAll()
            ->all();
    }

    public function findPayTypeLink(array $attributes, ?string $when = 'now'): ?CustomerPayTypeLink
    {
        foreach ($this->payTypeLinks() as $payTypeLink) {
            if ($payTypeLink->matches($attributes)) {
                if (dateTimeWithin($when, $payTypeLink->from, $payTypeLink->to)) {
                    return $payTypeLink;
                }
            }
        }
        return null;
    }

    public function findPayTypeLinks(array $attributes, string $from = null, string $to = null): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($from) && is_null($to)) {
            return $this->payTypeLinks();
        }

        $foundPayTypeLinks = [];

        foreach ($this->payTypeLinks() as $payTypeLink) {
            if ($payTypeLink->matches($attributes)) {
                if (intervalWithin($from, $to, $payTypeLink->from, $payTypeLink->to)) $foundPayTypeLinks[] = $payTypeLink;
            }
        }
        return $foundPayTypeLinks;
    }

    public function findActivePayTypeLinks(array $attributes, string $from = null, string $to = null): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($from) && is_null($to)) {
            return $this->activePayTypeLinks();
        }

        $foundPayTypeLinks = [];

        foreach ($this->activePayTypeLinks() as $payTypeLink) {
            if ($payTypeLink->matches($attributes)) {
                if (intervalWithin($from, $to, $payTypeLink->from, $payTypeLink->to)) $foundPayTypeLinks[] = $payTypeLink;
            }
        }
        return $foundPayTypeLinks;
    }

    public function getPayTypeLink(string $key): ?CustomerPayTypeLink
    {
        $payTypeLink = null;
        try {
            $payTypeLink = static::newQuery($this->getFullPath() . "/pay_type_links/$key")
                ->setModel(CustomerPayTypeLink::class)
                ->with([ 'contractTypes', 'absenceTypes', 'properties' ])
                ->get();
        } catch (Exception $e) {
            // TODO: Log error
        }
        return $payTypeLink;
    }

    public function hasPayTypeLink(array $attributes, string $when = 'now'): bool
    {
        return (bool)$this->findPayTypeLink($attributes, $when);
    }



    public function removePayTypeLink(CustomerPayTypeLink $payTypeLink): bool
    {
        try {
            eaw()->delete("/customers/$this->id/pay_type_links/$payTypeLink->id");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function copyPayTypeLink(CustomerPayTypeLink $payTypeLink, bool $addProperties = true, bool $addContractTypes = true, bool $addAbsenceTypes = true, bool $verbose = true): ?CustomerPayTypeLink
    {
        $tariffId = $payTypeLink->tariff_id;

        // Check if PTL has Tariff, and make sure its ID exists in SettingGroup
        if (!is_null($tariffId))
        {
            if ($payTypeLink->owner_type == 'customer')
            {
                // Check if PTL belonged to a Customer in the same SettingGroup
                $sourceCustomer = Customer::get($payTypeLink->owner_id);

                if ($this->settingGroup()->id !== $sourceCustomer->settingGroup()->id)
                {
                    $sourceTariff = $sourceCustomer->settingGroup()->getTariff($tariffId);

                    if (is_null($sourceTariff))
                    {
                        $tariffId = null;
                        if ($verbose) logg()->info($GLOBALS['fail'].'!');
                    }
                    else
                    {
                        $correctTariff = $this->settingGroup()->getTariff($sourceTariff->name, 'name');

                        if ($correctTariff) {
                            $tariffId = $correctTariff->id;
                        } else {
                            if ($verbose) logg()->info($GLOBALS['fail'].'?');
                        }
                    }
                }
            }
            elseif ($payTypeLink->owner_type == 'setting_group')
            {
                if ($this->settingGroup()->id !== $payTypeLink->owner_id)
                {
                    $sourceSettingGroup = SettingGroup::get($payTypeLink->owner_id);

                    $correctTariff = $this->settingGroup()->getTariff($sourceSettingGroup->getTariff($tariffId)->name, 'name');

                    if ($correctTariff) $tariffId = $correctTariff->id;
                }
            }
        }

        $properties = $payTypeLink->properties();

        // Check if PTL has Calculator
        if (!$payTypeLink->calculator_id)
        {
            // Get ReturnUnit for Manual PayType
            $returnUnit = 'currency'; // defaults to 'currency' for old manual ptls which do not have a returnUnit
            foreach ($properties as $property)
            {
                if ($property->key == 'returnUnit')
                {
                    $returnUnit = $property->value;
                    break;
                }
            }

            // Create Manual PTL
            $newPayTypeLink = CustomerPayTypeLink::newInstance(array_filter([
                'name' => $payTypeLink->name,
                'code' => $payTypeLink->code,
                'return_unit' => $returnUnit,
                'from' => $payTypeLink->from,
                'to' => $payTypeLink->to,
                'tariff_id' => $tariffId,
                'pay_type_id' => $payTypeLink->pay_type_id], static function($var) {return $var !== null;} )
            )->setPath("/customers/$this->id/pay_type_links");
        }
        else
        {
            try {
                // Create Calculator PTL
                $newPayTypeLink = CustomerPayTypeLink::newInstance(array_filter([
                        'name' => $payTypeLink->name,
                        'code' => $payTypeLink->code,
                        'calculator_id' => $payTypeLink->calculator_id,
                        'from' => $payTypeLink->from,
                        'to' => $payTypeLink->to,
                        'tariff_id' => $tariffId,
                        'pay_type_id' => $payTypeLink->pay_type_id], static function($var) {return $var !== null;} )
                )->setPath("/customers/$this->id/pay_type_links");
            } catch (\Exception $e) {
                return null;
            }
        }

        // Save
        $newPayTypeLink->save();
        if ($verbose) logg()->info($GLOBALS['success'].':');

        // Properties
        if ($addProperties)
        {
            foreach ($properties as $property)
            {
                try {
                    eaw()->create("/customers/$this->id/pay_type_links/$newPayTypeLink->id/properties", null, [
                        'key' => $property->key,
                        'value' => $property->value
                    ]);
                    if ($verbose) logg()->info($GLOBALS['success'].'.');
                }
                catch (Exception $e) {
                    if ($verbose) logg()->info($GLOBALS['exists'].'.');
                }
            }
        }

        // ContractTypes
        if ($addContractTypes)
        {
            $contractTypes = $payTypeLink->contractTypes();
            $contractTypeIds = [];

            if (count($contractTypes))
            {
                if ($verbose) echo '{';

                // Gather all ContractType IDs
                foreach ($contractTypes as $contractType)
                {
                    if ($contractType['setting_group_id'] !== $this->settingGroup()->id)
                    {
                        $correctContractType = $this->settingGroup()->getContractType($contractType['name'], 'name');
                        if (!$correctContractType) {
                            if ($verbose) logg()->info($GLOBALS['fail'].'?');
                            continue;
                        }
                        $contractTypeIds[] = $correctContractType->id;
                    }
                    else
                    {
                        $contractTypeIds[] = $contractType['id'];
                    }
                }

                // Try linking PTL with ContractType(s)
                try {
                    eaw()->update("/customers/$this->id/pay_type_links/$newPayTypeLink->id", null, [ 'contract_types' => $contractTypeIds ]);
                    if ($verbose) logg()->info($GLOBALS['success'].'ok');
                }
                catch (Exception $e) {
                    if ($verbose) logg()->info($GLOBALS['fail'].$e->getCode());
                }
                if ($verbose) echo '}';
            }
        }

        // AbsenceTypes
        if ($addAbsenceTypes)
        {
            $absenceTypes = $payTypeLink->absenceTypes();
            $absenceTypeIds = [];

            if (count($absenceTypes))
            {
                if ($verbose) echo '(';

                // Gather all AbsenceType IDs
                foreach ($absenceTypes as $absenceType)
                {
                    if ($absenceType['setting_group_id'] !== $this->settingGroup()->id)
                    {
                        $correctAbsenceType = $this->settingGroup()->getAbsenceType($absenceType['name'], 'name');
                        if (!$correctAbsenceType) {
                            if ($verbose) logg()->info($GLOBALS['fail'].'?');
                            continue;
                        }
                        $absenceTypeIds[] = $correctAbsenceType->id;
                    }
                    else
                    {
                        $absenceTypeIds[] = $absenceType['id'];
                    }
                }

                // Try linking PTL with AbsenceType(s)
                try {
                    eaw()->update("/customers/$this->id/pay_type_links/$newPayTypeLink->id", null, [ 'absence_types' => $absenceTypeIds ]);
                    if ($verbose) logg()->info($GLOBALS['success'].'ok');
                }
                catch (Exception $e) {
                    if ($verbose) logg()->info($GLOBALS['fail'].$e->getCode());
                }
                if ($verbose) echo ')';
            }
        }
        return $newPayTypeLink;
    }

    public function addManualPayTypeLink(string $name, string $code, string $returnUnit, ?int $payTypeId = null, ?string $from = null, ?string $to = null, array $properties = [], array $contractTypes = [], array $absenceTypes = [], ?int $tariffId = null, bool $verbose = true, string $effectOnLabor = 'negative', bool $includeInLaborCost = true, bool $includeInVacation = false, bool $useEmployeeRate = false): ?CustomerPayTypeLink
    {
        // Enum: modules/payroll/Calculators/ReturnUnit.php
        $validReturnUnits = ['currency', 'quantity', 'seconds', 'hours', 'days', 'weeks'];

        // Check if ReturnUnit is valid -- default to 'currency'
        if (!in_array($returnUnit, $validReturnUnits)) {
            // TODO: Log error
            if ($verbose) logg()->info($GLOBALS['fail'].'!');
            $returnUnit = 'currency';
        }

        // Check if Tariff exists in SettingGroup
        if ($tariffId)
        {
            if ($this::class == Customer::class)
            {
                if (!$this->settingGroup()->hasTariffWith(['id' => $tariffId]))
                {
                    // TODO: Log error
                    $tariffId = null;
                    if ($verbose) logg()->info($GLOBALS['fail'].'!');
                }
            }
            elseif ($this::class == SettingGroup::class)
            {
                if (!$this->hasTariffWith(['id' => $tariffId]))
                {
                    // TODO: Log error
                    $tariffId = null;
                    if ($verbose) logg()->info($GLOBALS['fail'].'!');
                }
            }
        }

        // Create Manual PTL
        $newPayTypeLink = CustomerPayTypeLink::newInstance(array_filter(
            [
                'name' => $name,
                'code' => $code,
                'return_unit' => $returnUnit,
                'from' => $from ?? utcNow(),
                'to' => $to,
                'tariff_id' => $tariffId,
                'pay_type_id' => $payTypeId,
                'effect_on_labor' => $effectOnLabor,
                'include_in_labor_cost' => $includeInLaborCost,
                'include_in_vacation' => $includeInVacation,
                'use_employee_rate' => $useEmployeeRate
            ], static function($var) {return $var !== null;} )
        )->setPath("/customers/$this->id/pay_type_links");

        // Save
        $newPayTypeLink->save();
        if ($verbose) logg()->info($GLOBALS['success'].':');

        // Properties
        foreach ($properties as $property)
        {
            try {
                eaw()->create("/customers/$this->id/pay_type_links/$newPayTypeLink->id/properties", null, [
                    'key' => $property->key,
                    'value' => $property->value
                ]);
                if ($verbose) logg()->info($GLOBALS['success'].'.');
            }
            catch (Exception $e) {
                if ($verbose) logg()->info($GLOBALS['exists'].'.');
            }
        }

        // ContractTypes
        if (count($contractTypes))
        {
            if ($verbose) echo '{';

            // Try linking PTL with ContractType(s)
            try {
                eaw()->update("/customers/$this->id/pay_type_links/$newPayTypeLink->id", null, [ 'contract_types' => $contractTypes ]);
                if ($verbose) logg()->info($GLOBALS['success'].'ok');
            }
            catch (Exception $e) {
                if ($verbose) logg()->info($GLOBALS['fail'].$e->getCode());
            }

            if ($verbose) echo '}';
        }

        // AbsenceTypes
        if (count($absenceTypes))
        {
            if ($verbose) echo '(';

            // Try linking PTL with AbsenceType(s)
            try {
                eaw()->update("/customers/$this->id/pay_type_links/$newPayTypeLink->id", null, [ 'absence_types' => $absenceTypes ]);
                if ($verbose) logg()->info($GLOBALS['success'].'ok');
            }
            catch (Exception $e) {
                if ($verbose) logg()->info($GLOBALS['fail'].$e->getCode());
            }

            if ($verbose) echo ')';
        }

        return $newPayTypeLink;
    }

    public function addPayTypeLink(string $name, string $code, int $calculatorId, ?int $payTypeId = null, ?string $from = null, ?string $to = null, array $properties = [], array $contractTypes = [], array $absenceTypes = [], ?int $tariffId = null, bool $verbose = true, string $effectOnLabor = 'negative', bool $includeInLaborCost = true, bool $includeInVacation = false, ?bool $useEmployeeRate = false, int $employeeRateRatio = 1): ?CustomerPayTypeLink
    {
        // Check if Tariff exists in SettingGroup
        if ($tariffId)
        {
            if ($this::class == Customer::class)
            {
                if (!$this->settingGroup()->hasTariffWith(['id' => $tariffId]))
                {
                    // TODO: Log error
                    $tariffId = null;
                    if ($verbose) logg()->info($GLOBALS['fail'].'!');
                }
            }
            elseif ($this::class == SettingGroup::class)
            {
                if (!$this->hasTariffWith(['id' => $tariffId]))
                {
                    // TODO: Log error
                    $tariffId = null;
                    if ($verbose) logg()->info($GLOBALS['fail'].'!');
                }
            }
        }

        // Create Manual PTL
        $newPayTypeLink = CustomerPayTypeLink::newInstance(array_filter(
                [
                    'name' => $name,
                    'code' => $code,
                    'calculator_id' => $calculatorId,
                    'from' => $from ?? utcNow(),
                    'to' => $to,
                    'tariff_id' => $tariffId,
                    'pay_type_id' => $payTypeId,
                    'effect_on_labor' => $effectOnLabor,
                    'include_in_labor_cost' => $includeInLaborCost,
                    'include_in_vacation' => $includeInVacation,
                    'use_employee_rate' => $useEmployeeRate,
                    'employee_rate_ratio' => $employeeRateRatio
                ], static function($var) {return $var !== null;} )
        )->setPath("/customers/$this->id/pay_type_links");

        // Save
        $newPayTypeLink->save();
        if ($verbose) logg()->info($GLOBALS['success'].':');

        // Properties
        foreach ($properties as $property)
        {
            try {
                eaw()->create("/customers/$this->id/pay_type_links/$newPayTypeLink->id/properties", null, [
                    'key' => $property->key,
                    'value' => $property->value
                ]);
                if ($verbose) logg()->info($GLOBALS['success'].'.');
            }
            catch (Exception $e) {
                if ($verbose) logg()->info($GLOBALS['exists'].'.');
            }
        }

        // ContractTypes
        if (count($contractTypes))
        {
            if ($verbose) echo '{';

            // Try linking PTL with ContractType(s)
            try {
                eaw()->update("/customers/$this->id/pay_type_links/$newPayTypeLink->id", null, [ 'contract_types' => $contractTypes ]);
                if ($verbose) logg()->info($GLOBALS['success'].'ok');
            }
            catch (Exception $e) {
                if ($verbose) logg()->info($GLOBALS['fail'].$e->getCode());
            }

            if ($verbose) echo '}';
        }

        // AbsenceTypes
        if (count($absenceTypes))
        {
            if ($verbose) echo '(';

            // Try linking PTL with AbsenceType(s)
            try {
                eaw()->update("/customers/$this->id/pay_type_links/$newPayTypeLink->id", null, [ 'absence_types' => $absenceTypes ]);
                if ($verbose) logg()->info($GLOBALS['success'].'ok');
            }
            catch (Exception $e) {
                if ($verbose) logg()->info($GLOBALS['fail'].$e->getCode());
            }

            if ($verbose) echo ')';
        }

        return $newPayTypeLink;
    }
}