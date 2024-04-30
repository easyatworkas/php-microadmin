<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

use Exception;

/**
 * @property mixed|null $id             PRIMARY KEY     OK
 * @property mixed|null $customer_id    FOREIGN KEY     OK
 * @property mixed|null $user_id        FOREIGN KEY     OK
 * @property mixed|null $from
 * @property mixed|null $to
 */
class CustomerAccess extends Model
{
    protected $path = '/users/{user}/customers';

    /**
     * Gets Customer Model from property $customer_id
     *
     * @return Customer
     * @author Torbjørn Kallstad
     */
    public function customer(): Customer
    {
        return Customer::get($this->customer_id);
    }

    /**
     * Gets User Model from property $user_id
     *
     * @return User
     * @author Torbjørn Kallstad
     */
    public function user(): User
    {
        return User::get($this->user_id);
    }

    /**
     * Terminate this CustomerAccess
     *
     * @param string $when
     *
     * @return bool
     * @throws Exception
     * @author Torbjørn Kallstad
     */
    public function terminate(string $when = 'now'): bool
    {
        return $this->update(['to' => dbTime($when)]);
    }
}
