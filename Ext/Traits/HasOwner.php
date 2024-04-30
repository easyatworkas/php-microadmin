<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete: TODO: Implement for Type default_hr_document
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\Customer;
use Ext\Models\Model;
use Ext\Models\SettingGroup;

/**
 * Trait for all Models with properties:
 * - owner_id
 * - owner_type
 */
trait HasOwner
{
    public function owner(): Model
    {
        switch ($this->owner_type) {
            case 'customer':
                return Customer::get($this->owner_id);
            case 'setting_group':
                return SettingGroup::get($this->owner_id);
            case 'default_hr_document':
                throw new Exception('Cannot get Owner of type ' . $this->owner_type);
            default:
                throw new Exception('Cannot get Owner of type ' . $this->owner_type);
        }
    }
}