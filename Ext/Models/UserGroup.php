<?php

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
 * @property mixed|null $name
 */
class UserGroup extends Model
{
    use HasMembers;
    use HasPermissions;
    use HasPermissionSets;

    protected $path = '/user_groups';
}
