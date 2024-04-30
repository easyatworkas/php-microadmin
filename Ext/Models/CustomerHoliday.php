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
 * @property mixed|null $date
 * @property mixed|null $year
 * @property mixed|null $country
 */
class CustomerHoliday extends Model
{
    protected $path = '/customers/{customer}/holidays';
}
