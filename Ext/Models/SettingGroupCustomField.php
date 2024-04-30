<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasProperties;

/**
 * @property mixed|null $id                         PRIMARY KEY         OK
 * @property mixed|null $model
 * @property mixed|null $custom_field_id
 * @property mixed|null $object_id
 * @property mixed|null $object_type
 * @property mixed|null $validator
 * @property mixed|null $required
 * @property mixed|null $default
 * @property mixed|null $has_interval
 * @property mixed|null $metadata
 */
class SettingGroupCustomField extends Model
{
    use HasProperties;

    protected $path = '/setting_groups/{setting_group}/custom_fields';

    // This is used by the Faker
    public function getRandomOption(): ?string
    {
        $options = $this->metadata['options'];

        if (empty($options)) return null;

        return array_rand($options);
    }
}
