<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

use Ext\Models\Property;

trait HasCustomProperties
{
    /**
     * Gets a list of all Custom Fields (Properties) attached to this Model
     *
     * @return array
     * @author Torbjørn Kallstad
     */
    public function customProperties(): array
    {
        $customProperties = [];

        foreach ($this->properties() ?? [] as $property) {
            if (str_starts_with($property->key, 'cf_')) {
                $customProperties[] = $property;
            }
        }

        return $customProperties;
    }

    /**
     * Gets a Custom Field (Property) by its Key
     *
     * @param string $key
     *
     * @return \Ext\Models\Property|null
     * @author Torbjørn Kallstad
     */
    public function getCustomProperty(string $key): ?Property
    {
        foreach ($this->customProperties() as $model) {
            if ($model->{$model->keyName} == $key) {
                return $model;
            }
        }
        return null;
    }

    /**
     * Gets a Custom Field (Property) matching a set of attributes, and when it was active.
     *
     * @param array  $attributes
     * @param string $when
     *
     * @return \Ext\Models\Property|null
     * @author Torbjørn Kallstad
     */
    public function findCustomProperty(array $attributes, string $when = 'now'): ?Property
    {
        foreach ($this->customProperties() as $model) {
            if ($model->matches($attributes)) {
                if (dateTimeWithin($when, $model['from'], $model['to'])) {
                    return $model;
                }
            }
        }
        return null;
    }

    /**
     * Checks if Custom Field (Property) exists on this Model
     *
     * @param array  $attributes
     * @param string $when
     *
     * @return bool
     * @author Torbjørn Kallstad
     */
    public function hasCustomProperty(array $attributes, string $when = 'now'): bool
    {
        return (bool)$this->findCustomProperty($attributes, $when);
    }

    /**
     * Updates the Value of a Custom Field (Property) key
     *
     * @param string $key
     * @param string $value
     *
     * @return bool
     * @author Torbjørn Kallstad
     */
    public function setCustomProperty(string $key, string $value, string $from = null, string $to = null): bool
    {
        return $this->update(array_filter([
            $key => $value,
//            'select_option' => $value,
            'from' => $from,
            'to' => $to,
        ], static function($var) {return $var !== null;} ));
    }
}





