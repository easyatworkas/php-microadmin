<?php

/*
 * [x] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\CustomerRotationInterval;

trait HasRotationIntervals
{

    public function intervals(): array
    {
        return $this->client->query($this->getFullPath() . '/intervals')
                ->setModel(CustomerRotationInterval::class)
                ->getAll()
                ->all();
    }
}