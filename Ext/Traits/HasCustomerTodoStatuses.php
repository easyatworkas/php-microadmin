<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\CustomerTodoStatus;

trait HasCustomerTodoStatuses
{
    public function todoStatuses(): array
    {
        if (!$this->hasProduct('Todo')) return [];

        return static::newQuery($this->getFullPath() . '/todo_statuses')
            ->setModel(CustomerTodoStatus::class)
            ->getAll()
            ->all();
    }

    public function hasTodoStatus(array $attributes): bool
    {
        return (bool)$this->findTodoStatus($attributes);
    }

    public function findTodoStatus(array $parameters): ?CustomerTodoStatus
    {
        foreach ($this->todoStatuses() as $status) {
            if ($status->matches($parameters)) {
                return $status;
            }
        }
        return null;
    }

    public function copyTodoStatus(CustomerTodoStatus $source, bool $checkExisting = true): ?CustomerTodoStatus
    {
        if (!$checkExisting || !$this->hasTodoStatus([ 'name' => $source->name ])) {

            // Add new Status
            return $this->addTodoStatus($source->name, $source->type);
        }
        return null;
    }

    public function addTodoStatus(string $name, string $type): ?CustomerTodoStatus
    {
        // Create new Status
        $newStatus = CustomerTodoStatus::newInstance([
            'name' => $name,
            'type' => $type
        ])->setPath("/customers/$this->id/todo_statuses");
        $newStatus->save();

        return $newStatus;
    }
}