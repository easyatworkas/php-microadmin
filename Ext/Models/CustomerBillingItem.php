<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                 PRIMARY KEY                             OK
 * @property mixed|null $customer_id        FOREIGN KEY                             OK
 * @property mixed|null $user_id            FOREIGN KEY                             OK
 * @property mixed|null $data               ['model_type', 'model_id', 'name']      OK
 * @property mixed|null $type
 * @property mixed|null $business_date
 * @property mixed|null $description
 * @property mixed|null $amount
 * @property mixed|null $billed
 */
class CustomerBillingItem extends Model
{
    protected $path = '/customers/{customer}/billing_items';

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
        return User::get($this->getAttribute('user_id'));
    }

    /**
     * Gets Model Type from property $data['model_id'] matched against property $date['model_type']
     *
     * @return \Ext\Models\Model|null
     * @throws \Exception
     * @author Torbjørn Kallstad
     */
    public function dataModel(): ?Model
    {
        $data = $this->getAttribute('data');

        switch ($data['model_type']) {
            case 'hr_file':
                foreach ($this->owner()->allEmployees() as $employee) {
                    $hrFile = $employee->getHrFile($data['model_id']);
                    if ($hrFile) {
                        return $hrFile;
                    }
                }
                return null;
            default:
                throw new \Exception('Not implemented for Model of Type ' . $data['model_type']);
        }

    }
}
