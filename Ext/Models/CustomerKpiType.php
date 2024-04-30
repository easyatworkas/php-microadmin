<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                 PRIMARY KEY     OK
 * @property mixed|null $name
 * @property mixed|null $provider_class
 * @property mixed|null $sum
 * @property mixed|null $type
 * @property mixed|null $prefix
 * @property mixed|null $suffix
 * @property mixed|null $key
 */
class CustomerKpiType extends Model
{
    protected $path = '/customers/{customer}/kpi_types';
}
