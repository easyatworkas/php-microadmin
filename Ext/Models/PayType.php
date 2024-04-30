<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                     PRIMARY KEY     OK
 * @property mixed|null $name
 * @property mixed|null $calculators_count
 */
class PayType extends Model
{
    protected $path = '/pay_types';
}
