<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

use Exception;

trait IsFlushable
{
    public function flush(bool $ancestors = false): bool
    {
        try {
            eaw()->create($this->getFullPath() . '/flush_settings', null, [ 'flush_ancestors' => $ancestors ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}