<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasProperties;

/**
 * @property mixed|null $class
 * @property mixed|null $from
 * @property mixed|null $to
 */
class SettingGroupObserver extends Model
{
    use HasProperties;

    protected $path = '/setting_groups/{setting_group}/observers';

    // Static Functions ------------------------------------------------------------------------------------------------
    public static function getAllInactiveForSettingGroup(int $id)
    {
        return static::newQuery('/observers')->group_id($id)->getAll();
    }
}
