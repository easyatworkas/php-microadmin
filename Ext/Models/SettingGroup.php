<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasCustomerPayTypeLinks;
use Ext\Traits\HasCustomerRelationships;
use Ext\Traits\HasMembers;
use Ext\Traits\HasOwner;
use Ext\Traits\HasParent;
use Ext\Traits\HasProperties;
use Ext\Traits\HasSettingGroupAbsenceTypes;
use Ext\Traits\HasSettingGroupContractTypes;
use Ext\Traits\HasSettingGroupCustomFields;
use Ext\Traits\HasSettingGroupObservers;
use Ext\Traits\HasSettingGroupTariffs;
use Ext\Traits\IsFlushable;

/**
 * @property mixed|null $id                     PRIMARY KEY         OK
 * @property mixed|null $name
 * @property mixed|null $model
 * @property mixed|null $type
 * @property mixed|null $owner_id
 * @property mixed|null $owner_type
 * @property mixed|null $parent_id              KEY (optional)
 */
class SettingGroup extends Model
{
    use IsFlushable;
    use HasCustomerPayTypeLinks;
    use HasCustomerRelationships;
    use HasOwner;
    use HasParent;
    use HasProperties;
    use HasSettingGroupAbsenceTypes;
    use HasSettingGroupContractTypes;
    use HasSettingGroupCustomFields;
    use HasSettingGroupObservers;
    use HasSettingGroupTariffs;
    use HasMembers;

    protected $path = '/setting_groups';

    public function copyProperty(Property $source): bool
    {
//        if (in_array($source->key, [ 'tbs.auth_token', 'tbs.site' ])) return false;

        if (!$this->hasProperty([ 'key' => $source->key ]))
        {
            $key = $source->key;

            // Check if this is a Dynamic Property | TODO: Should probably be handled elsewhere
            if ($source->isDynamic(['absence.type_']))
            {
                // Swap old ID with new ID
                $old_absence_type_id = abs((int) filter_var(explode('.', $key)[1], FILTER_SANITIZE_NUMBER_INT));
                $old_absence_type_name = $source->settingGroup()->getAbsenceType($old_absence_type_id)->name;
                $new_absence_type_id = $this->getAbsenceType($old_absence_type_name, 'name')->id;
                // Replace ID in Key
                $key = str_replace($old_absence_type_id, $new_absence_type_id, $key);
            }
            elseif ($source->isDynamic(['full_time_week_']))
            {
                // Swap old ID with new ID
                $old_contract_type_id = abs((int) filter_var($key, FILTER_SANITIZE_NUMBER_INT));
                $old_contract_type_name = $source->settingGroup()->getContractType($old_contract_type_id)->name;
                $new_contract_type_id = $this->getContractType($old_contract_type_name, 'name')->id;
                // Replace ID in Key
                $key = str_replace($old_contract_type_id, $new_contract_type_id, $key);
            }

            // Create Property
            eaw()->create("/setting_groups/$this->id/properties/", null, array_filter([
                'key' => $key,
                'value' => $source->value,
                'from' => $source->from,
                'to' => $source->to
            ], static function($var) {return $var !== null;} ));

            return true;
        }
        return false;
    }
}
