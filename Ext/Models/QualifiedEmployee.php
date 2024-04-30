<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                     PRIMARY KEY     OK
 * @property mixed|null $user_id                FOREIGN KEY     OK
 * @property mixed|null $name
 * @property mixed|null $number
 * @property mixed|null $pivot                  qualification_id
 *                                              from
 *                                              to
 *                                              rate
 */
class QualifiedEmployee extends Model
{
    protected $path = '/customers/{customer}/qualifications/{qualification}/employees';

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

    public function qualification_id(): int
    {
        return $this->pivot['qualification_id'];
    }

    public function from(): string
    {
        return $this->pivot['from'];
    }

    public function to(): string
    {
        return $this->pivot['to'];
    }

    public function rate(): string
    {
        return $this->pivot['rate'];
    }
}