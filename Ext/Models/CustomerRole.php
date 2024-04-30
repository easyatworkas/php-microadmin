<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasPermissions;
use Ext\Traits\HasPermissionSets;
use Ext\Traits\HasRoleAssignments;

/**
 * @property mixed|null $id             PRIMARY KEY         OK
 * @property mixed|null $customer_id    FOREIGN KEY         OK
 * @property mixed|null $name
 * @property mixed|null $parent_id      KEY (optional)      OK
 */
class CustomerRole extends Model
{
    use HasPermissions;
    use HasPermissionSets;
    use HasRoleAssignments;

    protected $path = '/customers/{customer}/roles';

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
     * Gets CustomerRole Model from optional property $parent_id
     *
     * @return \Ext\Models\CustomerRole|null
     * @author Torbjørn Kallstad
     */
    public function parent(): ?CustomerRole
    {
        return is_null($this->parent_id) ? null : $this->owner()->getRole($this->parent_id);
    }
}
