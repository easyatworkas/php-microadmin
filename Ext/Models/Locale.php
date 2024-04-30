<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $code               PRIMARY KEY     OK
 * @property mixed|null $name
 * @property mixed|null $customers_count
 */
class Locale extends Model
{
    public $keyName = 'code';
    protected $path = '/locales';
}
