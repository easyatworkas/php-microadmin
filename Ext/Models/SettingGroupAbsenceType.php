<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $name
 * @property mixed|null $span
 * @property mixed|null $from
 * @property mixed|null $to
 * @property mixed|null $paid
 * @property mixed|null $code
 * @property mixed|null $gradable
 * @property mixed|null $color
 * @property mixed|null $off_time
 */
class SettingGroupAbsenceType extends Model
{
    protected $path = '/setting_groups/{setting_group}/absence_types';
}
