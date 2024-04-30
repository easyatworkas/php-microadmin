<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\SettingGroupTariff;

trait HasSettingGroupTariffs
{
    public function tariffs(): array
    {
        return $this->client->query($this->getFullPath() . '/tariffs')->setModel(SettingGroupTariff::class)->getAll()->all();
    }
    public function getTariff( string $search, string $property = 'id' ): ?SettingGroupTariff
    {
        foreach ($this->tariffs() as $tariff)
        {
            if ($tariff->{$property} == $search) return $tariff;
        }
        return null;
    }
    public function hasTariffWith( array $parameters ): bool
    {
        foreach ($this->tariffs() as $tariff)
        {
            if ($tariff->matches($parameters)) return true;
        }
        return false;
    }
    public function addTariff(SettingGroupTariff $tariff, bool $checkExisting = true): bool
    {
        if (!$checkExisting || !$this->hasTariffWith([ 'name' => $tariff->name, 'type' => $tariff->type ]))
        {
            // Create new Tariff
            $newTariff = SettingGroupTariff::newInstance([
                'name' => $tariff->name,
                'type' => $tariff->type,
            ])->setPath('/setting_groups/'.$this->id.'/tariffs');

            // Save
            $newTariff->save();

            // Make sure instance is stored in database before trying to reference it
            usleep(60000);

            // Copy Tariff Rates
            foreach ($tariff->rates() as $rate)
            {
                $newTariff->addRate($rate);
            }

            // Copy Tariff Rules
            foreach ($tariff->rules() as $rule)
            {
                $newTariff->addRule($rule);
            }

            return true;
        }
        return false;
    }
}