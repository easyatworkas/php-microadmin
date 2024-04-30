<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                     PRIMARY KEY             OK
 * @property mixed|null $checklist_id           FOREIGN KEY             OK
 * @property mixed|null $parent_id              KEY                     OK
 * @property mixed|null $parent_type            used with parent_id     OK
 * @property mixed|null $title
 * @property mixed|null $description
 * @property mixed|null $type
 * @property mixed|null $user_id                FOREIGN KEY (optional)  OK
 * @property mixed|null $data
 * @property mixed|null $weight
 * @property mixed|null $options
 * @property mixed|null $deviates
 * @property mixed|null $todo_item_id           FOREIGN KEY (optional)  OK
 */
class ChecklistItem extends Model
{
    protected $path = '/customers/{customer}/checklists/{checklist}/items';

    /**
     * Gets CustomerChecklist Model from property $checklist_id
     *
     * @return \Ext\Models\CustomerChecklist
     * @author Torbjørn Kallstad
     */
    public function checklist(): CustomerChecklist
    {
        return Customer::get($this->getIdOf('customer'))->getChecklist($this->checklist_id);
    }

    /**
     * Gets Parent Model from property $parent_id based on $parent_type
     *
     * @return \Ext\Models\Model
     * @throws \Exception
     * @author Torbjørn Kallstad
     */
    public function parent(): Model
    {
        return match ($this->parent_type) {
            'checklist' => $this->checklist(),
            'checklists_category' => Customer::get($this->getIdOf('customer'))->findChecklistWithCategory($this->parent_id)->getCategory($this->parent_id),
            default => throw new \Exception("Not implemented for parent of type $this->parent_type"),
        };
    }

    /**
     * Gets User Model from optional property $user_id
     *
     * @return \Ext\Models\User|null
     * @author Torbjørn Kallstad
     */
    public function user(): ?User
    {
        return is_null($this->user_id) ? null : User::get($this->user_id);
    }

    /**
     * Gets CustomerTodoItem Model from optional property $todo_item_id
     *
     * @return \Ext\Models\CustomerTodoItem|null
     * @author Torbjørn Kallstad
     */
    public function todoItem(): ?CustomerTodoItem
    {
        return is_null($this->todo_item_id) ? null : Customer::get($this->getIdOf('customer'))->getTodoItem($this->todo_item_id);
    }
}