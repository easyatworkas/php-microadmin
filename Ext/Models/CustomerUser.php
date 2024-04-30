<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id             PRIMARY KEY     OK
 * @property mixed|null $customer_id    FOREIGN KEY     OK
 * @property mixed|null $user_id        FOREIGN KEY     OK
 * @property mixed|null $from
 * @property mixed|null $to
 * @property mixed|null $user           array
 */
class CustomerUser extends Model
{
    protected $path = '/customers/{customer}/users';

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
     * @author Torbjørn Kallstad
     */
    public function terminate(string $when = 'now'): bool
    {
        if ($when == 'now') {
            try {
                $this->delete();

                return true;
            } catch (\Exception $e) {
                return false;
            }
        } else {
            try {
                $this->update([ 'to' => $when ]);

                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
    }
}
