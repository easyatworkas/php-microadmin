<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                 PRIMARY KEY     OK
 * @property mixed|null $owner_id           FOREIGN KEY     OK
 * @property mixed|null $owner_type
 * @property mixed|null $name
 * @property mixed|null $description
 * @property mixed|null $from
 * @property mixed|null $to
 * @property mixed|null $color
 */
class CustomerCalendarEvent extends CalendarEvent
{
    protected $path = '/customers/{customer}/calendar_events';
}
