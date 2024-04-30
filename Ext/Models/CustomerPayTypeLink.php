<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasOwner;
use Ext\Traits\HasProperties;
use Ext\Traits\IsFlushable;

/**
 * @property mixed|null $id                         PRIMARY KEY             OK
 * @property mixed|null $code
 * @property mixed|null $name
 * @property mixed|null $calculator_id              FOREIGN KEY (optional)  OK
 * @property mixed|null $from
 * @property mixed|null $to
 * @property mixed|null $pay_type_id                FOREIGN KEY             OK
 * @property mixed|null $owner_id                   FOREIGN KEY             OK
 * @property mixed|null $owner_type
 * @property mixed|null $tariff_id                  FOREIGN KEY (optional)  OK
 * @property mixed|null $include_in_labor_cost
 * @property mixed|null $use_employee_rate
 * @property mixed|null $effect_on_labor
 * @property mixed|null $employee_rate_ratio
 * @property mixed|null $include_in_vacation
 * @property mixed|null $properties                 FOREIGN KEY (array)     OK
 * @property mixed|null $absence_types              FOREIGN KEY (array)     OK
 * @property mixed|null $contract_types             FOREIGN KEY (array)     OK
 */
class CustomerPayTypeLink extends Model
{
    use IsFlushable;
    use HasOwner;
    use HasProperties;

    protected $path = '/customers/{customer}/pay_type_links';

    /**
     * Gets Calculator Model from optional property $calculator_id
     *
     * @return \Ext\Models\Calculator|null
     * @author Torbjørn Kallstad
     */
    public function calculator(): ?Calculator
    {
        return is_null($this->calculator_id) ? null : Calculator::get($this->calculator_id);
    }

    /**
     * Gets Tariff Model from optional property $tariff_id
     *
     * @return \Ext\Models\SettingGroupTariff|null
     * @throws \Exception
     * @author Torbjørn Kallstad
     */
    public function tariff(): ?SettingGroupTariff
    {
        // TODO: Will this work for SettingGroupPayTypeLinks?
        return is_null($this->tariff_id) ? null : SettingGroup::get($this->owner()->settingGroup()->id)->getTariff($this->tariff_id);
    }

    /**
     * Gets PayType Model from optional(?) property $pay_type_id
     *
     * @return \Ext\Models\PayType|null
     * @author Torbjørn Kallstad
     */
    public function payType(): ?PayType
    {
        return is_null($this->pay_type_id) ? null : PayType::get($this->pay_type_id);
    }

    /**
     * Gets list of AbsenceType Models from optional property $absence_types
     *
     * @return array
     * @author Torbjørn Kallstad
     */
    public function absenceTypes(): array
    {
        if (!is_array($this->getAttribute('absence_types'))) {
            $withProperties = static::newQuery($this->getPath())
                ->with(['absence_types'])
                ->get($this->getKey());

            $this->attributes[ 'absence_types' ] = $withProperties->getAttribute('absence_types') ?? [];
        }

        return array_map(function (array $attributes) {
            return SettingGroupAbsenceType::newInstance($attributes);
        }, $this->getAttribute('absence_types'));
    }

    /**
     * Checks if AbsenceType exists on this PayTypeLink
     *
     * @param int $absenceTypeId
     *
     * @return bool
     * @author Torbjørn Kallstad
     */
    function hasAbsenceType(int $absenceTypeId): bool
    {
        foreach ($this->absenceTypes() as $absenceType) {
            if ($absenceType->id == $absenceTypeId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Adds AbsenceType to the list of $absence_types
     *
     * @param int $absenceTypeId
     *
     * @return bool
     * @author Torbjørn Kallstad
     */
    public function addAbsenceType(int $absenceTypeId): bool
    {
        $idArray = [];

        foreach ($this->absenceTypes() as $absenceType) {
            // Return true if absence type already exists
            if ($absenceType->id == $absenceTypeId) {
                return true;
            }

            $idArray[] = $absenceType->id;
        }

        // Add the AbsenceType
        $idArray[] = $absenceTypeId;

        // Update
        return $this->update([ 'absence_types' => $idArray ]);
    }

    /**
     * Gets list of ContractType Models from optional property $contract_types
     *
     * @return array
     * @author Torbjørn Kallstad
     */
    public function contractTypes(): array
    {
        if (!is_array($this->getAttribute('contract_types'))) {
            $withProperties = static::newQuery($this->getPath())
                ->with(['contract_types'])
                ->get($this->getKey());

            $this->attributes[ 'contract_types' ] = $withProperties->getAttribute('contract_types') ?? [];
        }

        return array_map(function (array $attributes) {
            return SettingGroupContractType::newInstance($attributes);
        }, $this->getAttribute('contract_types'));
    }

    /**
     * Checks if this PayTypeLink affects a specific ContractType
     *
     * @param int $contractTypeId
     *
     * @return bool
     * @author Torbjørn Kallstad
     */
    function affectsContractType(int $contractTypeId): bool
    {
        // If no ContractTypes are specified, it means it affects all ContractTypes
        if (empty($this->contractTypes())) {
            return true;
        }

        return $this->hasContractType($contractTypeId);
    }

    function hasOnlyContractType(int $contractTypeId): bool
    {
        return $this->affectsContractType($contractTypeId) && count($this->contractTypes()) == 1;
    }

    /**
     * Checks if ContractType exists on this PayTypeLink
     *
     * @param int $contractTypeId
     *
     * @return bool
     * @author Torbjørn Kallstad
     */
    function hasContractType(int $contractTypeId): bool
    {
        foreach ($this->contractTypes() as $contractType) {
            if ($contractType->id == $contractTypeId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Adds ContractType to the list of $contract_types
     *
     * @param int $contractTypeId
     *
     * @return bool
     * @author Torbjørn Kallstad
     */
    public function addContractType(int $contractTypeId): bool
    {
        $idArray = [];

        foreach ($this->contractTypes() as $contractType) {
            // Return true if contract type already exists
            if ($contractType->id == $contractTypeId) {
                return true;
            }

            $idArray[] = $contractType->id;
        }

        // Add the ContractType
        $idArray[] = $contractTypeId;

        // Update
        return $this->update([ 'contract_types' => $idArray ]);
    }

    /**
     * Swaps Calculator on this PayTypeLink
     *
     * @param int $calculatorId
     *
     * @return bool
     * @author Torbjørn Kallstad
     */
    public function swapCalculator(int $calculatorId): ?CustomerPayTypeLink
    {
        // Return this model if the Calculator is already set
        if ($this->calculator_id == $calculatorId) {
            return $this;
        }

        // Gather information about this PayTypeLink
        $properties = $this->properties();
        $absenceTypes = $this->absenceTypes();
        $contractTypes = $this->contractTypes();

        // Get Owner as Customer
        /** @var \Ext\Models\Customer $owner */
        $owner = $this->owner();

        // Create new PayTypeLink with the new Calculator
        try {
            $ptl = $owner->addPayTypeLink($this->name, $this->code, $calculatorId, $this->pay_type_id, $this->from, $this->to, $properties, $absenceTypes, $contractTypes, $this->tariff_id, false, $this->effect_on_labor, $this->include_in_labor_cost, $this->include_in_vacation, $this->use_employee_rate, $this->employee_rate_ratio);
        } catch (\Exception $e) {
            // TODO: Log error
            return null;
        }

        // Delete this PayTypeLink
        $this->delete();

        // Return the new PayTypeLink
        return $ptl;
    }
}
