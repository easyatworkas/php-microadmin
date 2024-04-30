<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\CustomerKpiType;

trait HasCustomerKpiTypes
{
    public function kpiTypes(): array
    {
        if (!$this->hasProduct('Key Performance Indicators')) return [];

        return static::newQuery($this->getFullPath() . '/kpi_types')
            ->setModel(CustomerKpiType::class)
            ->getAll()
            ->all();
    }
    public function getKpiType(string $key): ?CustomerKpiType
    {
        $kpiType = null;
        try {
            $kpiType = static::newQuery($this->getFullPath() . "/kpi_types/$key")
                ->setModel(CustomerKpiType::class)
                ->get();
        } catch (\Exception $e) {
            // TODO: Log error
        }
        return $kpiType;
    }
    public function addKpiType( CustomerKpiType $kpiType ): bool
    {
        if (!$this->hasKpiTypeWith([ 'id' => $kpiType->id ]))
        {
            // Create new KpiType
            eaw()->create("/customers/$this->id/kpi_types/", null, [
                'name' => $kpiType->key
            ]);

            return true;
        }
        return false;
    }
    public function hasKpiTypeWith( array $parameters ): ?CustomerKpiType
    {
        foreach ($this->kpiTypes() as $kpiType)
        {
            if ($kpiType->matches($parameters))
            {
                return $kpiType;
            }
        }
        return null;
    }
}