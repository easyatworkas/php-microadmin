<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $code                     PRIMARY KEY     OK
 * @property mixed|null $name
 */
class Country extends Model
{
    public $keyName = 'code';
    protected $path = '/countries';

    /**
     * Static function to get Country Model from CountryCode
     *
     * @param string $countryCode
     *
     * @return \Ext\Models\Country|null
     * @author Torbjørn Kallstad
     */
    public static function get(string $countryCode): ?Country
    {
        foreach (Country::getAll()->all() as $country) {
            if ($country->code == strtoupper($countryCode)) {
                return $country;
            }
        }
        return null;
    }

    /**
     * Gets list of CountryRegion Models for this Country
     *
     * @return array
     * @author Torbjørn Kallstad
     */
    public function regions(): array
    {
        return $this->client->query($this->getFullPath() . '/regions')
            ->setModel(CountryRegion::class)
            ->getAll()
            ->all();
    }

    /**
     * Gets (related) Region Model by its Key
     *
     * @param string $key
     *
     * @return \Ext\Models\CountryRegion|null
     * @author Torbjørn Kallstad
     */
    public function getRegion(string $key): ?CountryRegion
    {
        foreach ($this->regions() as $model) {
            if ($model->{$model->keyName} == $key) {
                return $model;
            }
        }
        return null;
    }
}