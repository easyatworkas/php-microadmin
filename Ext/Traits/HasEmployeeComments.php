<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\Customer;
use Ext\Models\EmployeeComment;

trait HasEmployeeComments
{
    protected ?array $myNotes = null;
    public function notes(): array
    {
        return !is_null($this->myNotes)
            ? $this->myNotes
            : $this->myNotes = $this->client->query($this->getFullPath() . '/comments')->setModel(EmployeeComment::class)->getAll()->all();
    }
}