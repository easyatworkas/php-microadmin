<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                     PRIMARY KEY             OK
 * @property mixed|null $customer_id            FOREIGN KEY             OK
 * @property mixed|null $creator_id             FOREIGN KEY             OK
 * @property mixed|null $responsible_id         FOREIGN KEY (optional)  OK
 * @property mixed|null $notify
 * @property mixed|null $title
 * @property mixed|null $description
 * @property mixed|null $from
 * @property mixed|null $due
 * @property mixed|null $status_id              FOREIGN KEY             OK
 * @property mixed|null $object_id              FOREIGN KEY (optional)  OK
 * @property mixed|null $object_type            used with object_id     OK
 * @property mixed|null $duehack
 * @property mixed|null $status_at
 * @property mixed|null $time_spent
 */
class CustomerTodoItem extends Model
{
    protected $path = '/customers/{customer}/todo_items';

    /**
     * Gets Customer Model from property $customer_id
     *
     * @return \Ext\Models\Customer
     * @author Torbjørn Kallstad
     */
    public function owner(): Customer
    {
        return Customer::get($this->customer_id);
    }

    /**
     * Gets User Model from property $creator_id
     *
     * @return \Ext\Models\User
     * @author Torbjørn Kallstad
     */
    public function creator(): User
    {
        return User::get($this->creator_id);
    }

    /**
     * Gets User Model from property $responsible_id
     *
     * @return \Ext\Models\User|null
     * @author Torbjørn Kallstad
     */
    public function responsible(): ?User
    {
        return is_null($this->responsible_id) ? null : User::get($this->responsible_id);
    }

    /**
     * Gets CustomerTodoStatus Model from property $status_id
     *
     * @return \Ext\Models\CustomerTodoStatus
     * @author Torbjørn Kallstad
     */
    public function status(): CustomerTodoStatus
    {
        return $this->owner()->getTodoStatus($this->status_id);
    }

    /**
     * Gets Model from property $object_id based on $object_type
     *
     * @return \Ext\Models\Model
     * @throws \Exception
     * @author Torbjørn Kallstad
     */
    public function object(): Model
    {
        return match ($this->object_type) {
            'checklist' => $this->owner()->getChecklist($this->object_id),
            'checklists_item' => $this->owner()->findChecklistWithItem($this->object_id)->getItem($this->object_id),
            default => throw new \Exception("Not implemented for object of type $this->object_type"),
        };
    }

    /**
     * Check if TodoItem is not started (default state)
     *
     * @return bool
     * @author Torbjørn Kallstad
     */
    public function isNotStarted(): bool
    {
        return $this->status()->type == 'default';
    }

    /**
     * Check if TodoItem is in progress
     *
     * @return bool
     * @author Torbjørn Kallstad
     */
    public function isInProgress(): bool
    {
        return $this->status()->type == 'in_progress';
    }

    /**
     * Check if TodoItem is done/complete
     *
     * @return bool
     * @author Torbjørn Kallstad
     */
    public function isDone(): bool
    {
        return $this->status()->type == 'done';
    }
}
