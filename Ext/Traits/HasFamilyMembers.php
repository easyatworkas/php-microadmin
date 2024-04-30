<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\FamilyMember;

trait HasFamilyMembers
{
    public function familyMembers(): array
    {
        if (!$this->employer()->hasProduct('Switzerland')) return [];

        return $this->client->query($this->getFullPath() . '/family_members')
            ->setModel(FamilyMember::class)
            ->getAll()
            ->all();
    }
}