<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasOwner;

/**
 * @property mixed|null $id                 PRIMARY KEY     OK
 * @property mixed|null $class
 * @property mixed|null $owner_id           FOREIGN KEY     OK
 * @property mixed|null $owner_type
 * @property mixed|null $name
 * @property mixed|null $description
 * @property mixed|null $formats            array
 */
class CustomerReport extends Model
{
    use HasOwner;

    protected $path = '/customers/{customer}/reports';
}
