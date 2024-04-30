<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

trait HasEmployeeAvailabilities
{
    public function isAvailable(string $when = 'now'): bool
    {
        return !$this->findAvailability([], $when);
    }
}