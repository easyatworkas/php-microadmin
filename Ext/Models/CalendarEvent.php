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
class CalendarEvent extends Model
{
    /**
     * Gets Model from property $owner_id, matched against property $owner_type
     *
     * @return \Ext\Models\Model
     * @throws \Exception
     * @author Torbjørn Kallstad
     */
    public function owner(): Model
    {
        return match ($this->owner_type) {
            'customer' => Customer::get($this->owner_id),
            default => throw new \Exception('Cannot get Owner of type ' . $this->owner_type),
        };
    }
}
