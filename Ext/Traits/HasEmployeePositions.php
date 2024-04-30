<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\Customer;
use Ext\Models\EmployeePosition;

trait HasEmployeePositions
{
    protected ?array $myPositions = null;
    public function positions(): array
    {
        return !is_null($this->myPositions)
            ? $this->myPositions
            : $this->myPositions = $this->client->query($this->getFullPath() . '/positions')->setModel(EmployeePosition::class)->getAll()->all();
    }
}