<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasPermissions;
use Ext\Traits\HasPermissionSets;

/**
 * @property mixed|null $id                         PRIMARY KEY     OK
 * @property mixed|null $user_id                    FOREIGN KEY
 * @property mixed|null $name
 * @property mixed|null $secret
 * @property mixed|null $language_code
 * @property mixed|null $redirect
 * @property mixed|null $personal_access_client
 * @property mixed|null $password_client
 * @property mixed|null $revoked
 */
class OauthClient extends Model
{
    use HasPermissions;
    use HasPermissionSets;

    protected $path = '/clients';

    public function customers(): array
    {
//        if ($this->isAllowed('*')) return ['*']; TODO: Activate this when PermissionCheckController has been fixed

        $permissionNodes = array_column($this->permissions(), 'node');

        $customerIds = [];

        foreach ($permissionNodes as $node) {
            $parts = explode('.', $node);

            if (count($parts) >= 2 && $parts[0] === 'customers') {
                $customerId = $parts[1];
                $customerIds[$customerId] = true;
            }
        }

        return array_keys($customerIds);
    }

    public function isAllowed(string $permission): bool
    {
        return eaw()->read($this->getFullPath() . '/is_allowed_many', [ 'permissions' => [ $permission ], 'mode' => 'isAllowed' ])[$permission];
    }

    public function regenerateSecret(): string
    {
        $this->update(['regenerate_secret' => true]);
        return $this->secret;
    }
}
