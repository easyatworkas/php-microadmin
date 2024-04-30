<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\SettingGroupContractType;

trait HasSettingGroupContractTypes
{
    public function contractTypes(): array
    {
        return $this->client->query($this->getFullPath() . '/contract_types')->setModel(SettingGroupContractType::class)->getAll()->all();
    }
    public function getContractType( string $search, string $property = 'id' ): ?SettingGroupContractType
    {
        foreach ($this->contractTypes() as $contractType)
        {
            if ($contractType->{$property} == $search) return $contractType;
        }
        return null;
    }
    public function hasContractTypeWith( array $parameters ): bool
    {
        foreach ($this->contractTypes() as $contractType)
        {
            if ($contractType->matches($parameters)) return true;
        }
        return false;
    }
    public function addContractType( SettingGroupContractType $contractType, bool $checkExisting = true ): bool
    {
        if (!$checkExisting || !$this->hasContractTypeWith([ 'name' => $contractType->name ]))
        {
            eaw()->create('/setting_groups/' . $this->id . '/contract_types/', null, array_filter([
                'name' => $contractType->name,
                'from' => $contractType->from,
                'to' => $contractType->to,
            ], static function($var) {return $var !== null;} ));
            return true;
        }
        return false;
    }

    public function getRandomContractType(): SettingGroupContractType
    {
        $array = $this->contractTypes();
        return $array[rand(0,count($array)-1)];
    }
}