<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\CustomerPeriodLock;

trait HasCustomerPeriodLocks
{
    public function periodLocks(): array
    {
        if (!$this->hasProduct('Period Locks')) return [];

        return static::newQuery($this->getFullPath().'/period_locks')
            ->setModel(CustomerPeriodLock::class)
            ->getAll()
            ->all();
    }

    public function getPeriodLock(string $key): ?CustomerPeriodLock
    {
        $periodLock = null;
        try {
            $periodLock = static::newQuery($this->getFullPath() . "/period_locks/$key")
                ->setModel(CustomerPeriodLock::class)
                ->get();
        } catch (Exception $e) {
            // TODO: Log error
        }
        return $periodLock;
    }
}