<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete: TODO: Use Trait "IsCommentable" instead?
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                 PRIMARY KEY     OK
 * @property mixed|null $commentable_id     FOREIGN KEY     OK
 * @property mixed|null $commentable_type
 * @property mixed|null $user_id            FOREIGN KEY     OK
 * @property mixed|null $user_name
 * @property mixed|null $body
 */
class EmployeeComment extends Model
{
    protected $path = '/customers/{customer}/employees/{employee}/comments';

    /**
     * Gets Employee Model from property $commentable_id
     *
     * @return \Ext\Models\Employee
     * @author Torbjørn Kallstad
     */
    public function employee(): Employee
    {
        return Customer::get($this->getIdOf('customer'))->getEmployee($this->commentable_id);
    }

    /**
     * Gets User Model from property $user_id
     *
     * @return \Ext\Models\User
     * @author Torbjørn Kallstad
     */
    public function user(): User
    {
        return User::get($this->user_id);
    }
}
