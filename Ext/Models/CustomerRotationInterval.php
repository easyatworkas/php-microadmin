<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id             PRIMARY ID      OK
 * @property mixed|null $rotation_id    FOREIGN ID      OK
 * @property mixed|null $day_index
 * @property mixed|null $from
 * @property mixed|null $to
 */
class CustomerRotationInterval extends Model
{
    protected $path = '/customers/{customer}/rotations/{rotation}/intervals';

    /**
     * Gets Rotation Model from property $rotation_id
     *
     * @return \Ext\Models\CustomerRotation
     * @author Torbjørn Kallstad
     */
    public function rotation(): CustomerRotation
    {
        return Customer::get($this->getIdOf('customer'))->getRotation($this->rotation_id);
    }
}
