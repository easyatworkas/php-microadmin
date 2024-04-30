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
 * @property mixed|null $iso639_1
 * @property mixed|null $ietf_bcp47_tag
 * @property mixed|null $users_count
 */
class Language extends Model
{
    public $keyName = 'code';
    protected $path = '/languages';
}
