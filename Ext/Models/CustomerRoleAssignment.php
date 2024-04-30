<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id             PRIMARY KEY         OK
 * @property mixed|null $role_id        FOREIGN KEY         OK
 * @property mixed|null $user_id        FOREIGN KEY         OK
 * @property mixed|null $from
 * @property mixed|null $to
 */
class CustomerRoleAssignment extends Model
{
    protected $path = '/customers/{customer}/roles/{role}/assignments';

    /**
     * Gets CustomerRole Model from property $role_id
     *
     * @return \Ext\Models\CustomerRole
     * @author Torbjørn Kallstad
     */
    public function role(): CustomerRole
    {
        return Customer::get($this->getIdOf('customer'))->getRole($this->role_id);
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
