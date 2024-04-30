<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\SettingGroupCustomField;

trait HasSettingGroupCustomFields
{
    public function customFields(): array
    {
        return $this->client->query($this->getFullPath() . '/custom_fields')
            ->setModel(SettingGroupCustomField::class)
            ->getAll()
            ->all();
    }

    public function getCustomField(string $key): ?SettingGroupCustomField
    {
        foreach ($this->customFields() as $model) {
            if ($model->{$model->keyName} == $key) {
                return $model;
            }
        }
        return null;
    }

    public function findCustomField(array $attributes): ?SettingGroupCustomField
    {
        foreach ($this->customFields() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function findCustomFields(array $attributes): array
    {
        $found = [];

        foreach ($this->customFields() as $model) {
            if ($model->matches($attributes)) {
                $found[] = $model;
            }
        }
        return $found;
    }

    public function hasCustomField(array $attributes): bool
    {
        return (bool)$this->findCustomField($attributes);
    }

    public function addCustomField(SettingGroupCustomField $customField): ?SettingGroupCustomField
    {
        if (!$this->hasCustomField([ 'custom_field_id' => $customField->custom_field_id ])) {
            // Create new CustomField
            $newCustomField = SettingGroupCustomField::newInstance(array_filter([
                'custom_field_id' => $customField->custom_field_id,
                'model' => $customField->model,
                'object_type' => $customField->object_type,
                'required' => $customField->required,
                'validator' => $customField->validator,
                'default' => $customField->default,
                'has_interval' => $customField->has_interval,
                'metadata' => is_string($customField->metadata) ? json_decode($customField->metadata, true) : $customField->metadata,
            ], static function ($var) {
                return $var !== null;
            }
            ))->setPath("/setting_groups/$this->id/custom_fields");

            // Save
            $newCustomField->save();

            return $newCustomField;
        }
        return null;
    }
}