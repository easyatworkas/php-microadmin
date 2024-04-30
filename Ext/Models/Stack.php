<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasProperties;

/**
 * @property mixed|null $id                     PRIMARY KEY     OK
 * @property mixed|null $region
 * @property mixed|null $name
 * @property mixed|null $customers_count
 * @property mixed|null $provisioned
 * @property mixed|null $properties
 */
class Stack extends Model
{
    use HasProperties;

    protected $path = '/stacks';
}
