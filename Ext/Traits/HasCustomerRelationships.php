<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\Customer;
use Ext\Models\CustomerLink;

trait HasCustomerRelationships
{
    public function relationships(): array
    {
        return static::newQuery($this->getFullPath() . '/links')
            ->setModel(CustomerLink::class)
            ->getAll()
            ->all();
    }

    public function activeRelationships(): array
    {
        return static::newQuery($this->getFullPath() . '/links')
            ->setModel(CustomerLink::class)
            ->active(true)
            ->getAll()
            ->all();
    }

    public function getCustomerLink(int $key): ?CustomerLink
    {
        foreach ($this->relationships() as $relation)
        {
            if ($relation->id == $key) return $relation;
        }
        return null;
    }

    public function getRelatives(?int $id, bool $active = true): array
    {
        if (is_null($id)) return [];

        // Initialize arrays
        $relationships = [];
        $parents = [];
        $children = [];
        $siblings = [];

        // Get all Relationships for the provided $id
        $relations = eaw()->readPaginated("/customers/$id/links", [ 'active' => $active ]);

        // Find all parents & children
        foreach ($relations as $relation)
        {
            // Child
            if ($relation['parent']['id'] == $id) $children[] = $relation['child']['id'];
            // Parent
            if ($relation['child']['id'] == $id) $parents[] = $relation['parent']['id'];
        }

        // Find all links
        $links = array_intersect($children, $parents);

        // Find true children
        $children = array_diff($children, $links);

        // Find true parents
        $parents = array_diff($parents, $links);

        // Find all siblings
        foreach ($parents as $parent)
        {
            // Merge full- and half-siblings
            $siblings = array_merge(array_values(array_diff($this->getRelatives($parent, $active)['children'] ?? [], array($id))), $siblings);
        }

        // Gather all
        $relationships['id'] = $id;
        $relationships['links'] = $links;
        $relationships['parents'] = $parents;
        $relationships['siblings'] = $siblings;
        $relationships['children'] = $children;

        // Return Relationships
        return $relationships;
    }

    private function getRelations( string $relation, bool $includeInactive = false ): array
    {
        $myRelatives = $this->getRelatives($this->id, !$includeInactive);

        // Return
        return $relation == 'all' ? $myRelatives : $myRelatives[$relation];
    }

    private function getOrigin( int $id, array $relatives ): ?int
    {
        // First check if customer has one parent
        $parents = $relatives['parents'];

        if (count($parents) == 1)
        {
            $origin = current($parents);

            if (is_null($origin)) return $id;

            do
            {
                $new_origin = $this->getOrigin($origin, $this->getRelatives($origin));
                if ($new_origin == $origin) $new_origin = null;

                if (is_null($new_origin))
                {
                    return $origin;
                }
                else
                {
                    $origin = $new_origin;
                }
            }
            while (true);
        }
        return $id;
    }

    public function origin(): ?int
    {
        return $this->getOrigin($this->id, $this->getRelations('all'));
    }

    public function parents(bool $includeInactive = false): array
    {
        return $this->getRelations('parents', $includeInactive);
    }

    public function hasParent(): bool
    {
        return !empty($this->parents());
    }

    public function hasChildren(): bool
    {
        return !empty($this->children());
    }

    public function parentCustomer(): ?Customer
    {
        $parents = $this->parents();

        return match (count($parents)) {
            1 => Customer::get($parents[0]),
            default => null,
        };
    }

    public function children(bool $includeInactive = false): array
    {
        return $this->getRelations('children', $includeInactive);
    }

    public function childCustomers(): array
    {
        $children = $this->children();

        return array_map(static function($child) { return Customer::get($child); }, $children);
    }

    public function siblings(bool $includeInactive = false): array
    {
        return $this->getRelations('siblings', $includeInactive);
    }

    public function links(bool $includeInactive = false): array
    {
        return $this->getRelations('links', $includeInactive);
    }

    public function makeParentOf(int $customerId, string $from = null, string $type = 'link'): bool
    {
        try {
            eaw()->create("/customers/$this->id/links/", null, array_filter([
                'child' => $customerId,
                'type' => $type,
                'from' => $from,
            ], static function($var) {return $var !== null;} ));

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function makeChildOf(int $customerId, string $from = null, string $type = 'link'): bool
    {
        try {
            eaw()->create("/customers/$customerId/links/", null, array_filter([
                'child' => $this->id,
                'type' => $type,
                'from' => $from,
            ], static function($var) {return $var !== null;} ));

            return true;
        } catch (\Exception $e) {
            return false;
        }

    }

    public function endMyRelationshipWithChild($childCustomerId, string $to): bool
    {
        try {
            eaw()->update('/customers/' . $this->id . '/links/' . $childCustomerId, null, ['to' => $to]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function copyRelation(CustomerLink $source, Customer $original): bool
    {
        if ($source->parent['id'] == $original->id) {
            // Set $this as parent
            return $this->makeParentOf($source->child['id']);
        } else {
            // Set $this as child
            return $this->makeChildOf($source->parent['id']);
        }
    }

    public function descendants(?int $parentId = null): array
    {
        $branch = [];
        $customer = is_null($parentId) ? $this : Customer::get($parentId);
        foreach ($customer->children() as $child) {
            $branch[$child] = $this->descendants($child);
        }
        return $branch;
    }

    public function newDescendants(): array
    {
        $branch = [];

        foreach ($this->children() as $childId)
        {
            $child = Customer::get($childId);
            $branch[] = [$child->id => ['model' => $child, 'descendants' => $child->newDescendants()]];
        }

        return $branch;
    }
}