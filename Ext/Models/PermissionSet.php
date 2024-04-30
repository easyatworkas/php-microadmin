<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasPermissions;
use Ext\Traits\HasPermissionSets;

/**
 * @property mixed|null $id             PRIMARY KEY
 * @property mixed|null $name
 * @property mixed|null $product_name
 * @property mixed|null $description
 */
class PermissionSet extends Model
{
    use HasPermissions;
    use HasPermissionSets;

    protected $path = '/permission_sets';
}
