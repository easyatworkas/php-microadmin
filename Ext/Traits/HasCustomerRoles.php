<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\CustomerRole;

trait HasCustomerRoles
{
    protected ?array $myRoles = null;
    public function roles(): array
    {
        return !is_null($this->myRoles)
            ? $this->myRoles
            : $this->myRoles = $this->client->query($this->getFullPath().'/roles')->setModel(CustomerRole::class)->getAll()->all();
    }
    public function getRole( string $search, string $property = 'id' ): ?CustomerRole
    {
        foreach ($this->roles() as $role)
        {
            if ($role->{$property} == $search)
            {
                return $role;
            }
        }
        return null;
    }
    public function hasRoleWith( array $parameters ): ?CustomerRole
    {
        foreach ($this->roles() as $role)
        {
            if ($role->matches($parameters))
            {
                return $role;
            }
        }
        return null;
    }
    public function addRole(CustomerRole $role): bool
    {
        // TODO: Assignments, Permissions and PermissionSets are not handled yet
        if (is_null($role->parent_id))
        {
            // Role has no Parent, so create directly
            eaw()->create("/customers/$this->id/roles", null, [
                'name' => $role->name
            ]);
        }
        else // Create the Parent Role first
        {
            // Get parent of the source Role
            $parent = CustomerRole::customer($role->customer_id)->get($role->parent_id);

            // Create new parent Role at this Customer
            $this->addRole($parent); // This will recursively create the whole hierarchy tree for this node

            // Fetch the new (or existing) parent Role
            $newParent = $this->getRole($parent->id);

            // We have the new Parent, so Model can be created
            eaw()->create("/customers/$this->id/roles", null, [
                'name' => $role->name,
                'parent_id' => $newParent->id
            ]);
        }
        return true;
    }
}