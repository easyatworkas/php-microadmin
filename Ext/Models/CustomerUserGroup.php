<?php // TODO: UserGroups have Responsible .../responsible

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasMembers;
use Ext\Traits\HasPermissions;
use Ext\Traits\HasPermissionSets;

/**
 * @property mixed|null $id                     PRIMARY KEY     OK
 * @property mixed|null $name
 * @property mixed|null $model
 * @property mixed|null $type
 * @property mixed|null $owner_id               FOREIGN KEY
 * @property mixed|null $owner_type
 * @property mixed|null $parent_id              KEY
 */
class CustomerUserGroup extends Model
{
    use HasMembers;
    use HasPermissions;
    use HasPermissionSets;

    protected $path = '/customers/{customer}/user_groups';

    /**
     * Gets Customer Model from property $customer_id
     *
     * @return \Ext\Models\Customer
     * @author Torbjørn Kallstad
     */
    public function owner(): Customer
    {
        return Customer::get($this->owner_id);
    }
}