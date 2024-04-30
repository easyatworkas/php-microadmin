<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                     PRIMARY KEY         OK
 * @property mixed|null $name
 * @property mixed|null $type
 * @property mixed|null $key
 */
class CustomField extends Model
{
    protected $path = '/custom_fields';
}
