<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                 PRIMARY KEY     OK
 * @property mixed|null $availability_id    FOREIGN KEY
 * @property mixed|null $day                0=monday, 1=tuesday, etc.
 * @property mixed|null $offset
 * @property mixed|null $length
 * @property bool       $whole_day
 * @property mixed|null $from
 * @property mixed|null $to
 */
class AvailabilityDay extends Model
{
    // Nothing to see here...
}
