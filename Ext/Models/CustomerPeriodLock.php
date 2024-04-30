<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                     PRIMARY KEY     OK
 * @property mixed|null $customer_id            FOREIGN KEY     OK
 * @property mixed|null $user_id                FOREIGN KEY     OK
 * @property mixed|null $to
 */
class CustomerPeriodLock extends Model
{
    protected $path = '/customers/{customer}/period_locks';

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
