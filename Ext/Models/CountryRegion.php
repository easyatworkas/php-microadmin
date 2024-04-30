<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                 PRIMARY KEY     OK
 * @property mixed|null $code
 * @property mixed|null $country_code
 * @property mixed|null $name
 */
class CountryRegion extends Model
{
    protected $path = '/countries/{country}/regions';
}
