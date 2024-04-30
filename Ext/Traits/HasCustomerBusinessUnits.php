<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\CustomerBusinessUnit;

trait HasCustomerBusinessUnits
{
    public function businessUnits(): array
    {
        if (!$this->hasProduct('BusinessUnits')) return [];

        return static::newQuery($this->getFullPath() . '/business_units')
            ->setModel(CustomerBusinessUnit::class)
            ->with(['qualifications'])
            ->getAll()
            ->all();
    }

    public function getBusinessUnit(string $key): ?CustomerBusinessUnit
    {
        $businessUnit = null;
        try {
            $businessUnit = static::newQuery($this->getFullPath() . "/business_units/$key")
                ->setModel(CustomerBusinessUnit::class)
                ->with(['qualifications'])
                ->get();
        } catch (\Exception $e) {
            // TODO: Log error
        }
        return $businessUnit;
    }

    public function findBusinessUnit(array $attributes): ?CustomerBusinessUnit
    {
        foreach ($this->businessUnits() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function findBusinessUnits(array $attributes): array
    {
        // If no params provided, return all
        if (empty($attributes)) {
            return $this->businessUnits();
        }

        $found = [];

        foreach ($this->businessUnits() as $model) {
            if ($model->matches($attributes)) {
                $found[] = $model;
            }
        }
        return $found;
    }

    public function hasBusinessUnit(array $attributes): bool
    {
        return (bool)$this->findBusinessUnit($attributes);
    }

    public function addBusinessUnit(string $name, bool $checkExisting = true, ?string $color = 'random', $default = null, $topParent = null): ?CustomerBusinessUnit
    {
        if (!$checkExisting || !$this->hasBusinessUnit([ 'name' => $name ]))
        {
            $newColor = $color == 'random' ? '#' . substr(md5(mt_rand()), 0, 6) : $color;

            $model = CustomerBusinessUnit::newInstance(array_filter([
                'name' => $name,
                'color' => $newColor,
                'default' => $default,
                'top_parent' => $topParent,
            ], static function ($var) {
                return $var !== null;
            }))->setPath("/customers/$this->id/business_units");

            $model->save();
            return $model;
        }
        return null;
    }

    public function hasBusinessUnitWith(array $parameters): ?CustomerBusinessUnit
    {
        foreach ($this->businessUnits() as $businessUnit) {
            if ($businessUnit->matches($parameters)) {
                return $businessUnit;
            }
        }
        return null;
    }
}