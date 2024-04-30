<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\Customer;
use Ext\Models\Property;
use Ext\Models\SettingGroup;

trait HasProperties
{
    public function properties(): array
    {
        if ($this->hasPropertyController ?? true) {
            return static::newQuery($this->getFullPath() . '/properties')
                ->setModel(Property::class)
                ->getAll()
                ->all();
        }

        if (!is_array($this->getAttribute('properties'))) {
            $withProperties = static::newQuery($this->getPath())
                ->with(['properties'])
                ->get($this->getKey());

            $this->attributes['properties'] = $withProperties->getAttribute('properties') ?? [];
        }

        $this->myProperties = array_map(function (array $attributes) {
            return Property::newInstance($attributes);
        }, $this->getAttribute('properties'));

        return $this->myProperties;
    }

    public function findProperty(array $attributes, string $when = 'now'): ?Property
    {
        foreach ($this->properties() as $property) {
            if ($property->matches($attributes)) {
                if (dateTimeWithin($when, $property->from, $property->to)) {
                    return $property; //->setPath($this->getFullPath() . '/properties');
                }
            }
        }
        return null;
    }

    public function findProperties(array $attributes, string $from = null, string $to = null): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($from) && is_null($to)) {
            return $this->properties();
        }

        $foundProperties = [];

        foreach ($this->properties() as $property) {
            if ($property->matches($attributes)) {
                if (intervalWithin($property->from, $property->to, $from, $to)) {
                    $foundProperties[] = $property;
                }
            }
        }
        return $foundProperties;
    }

    // TODO: Dammit, this does not work for properties, because keys can be both strings and integers
    public function getProperty(string $key): ?Property
    {
        $property = null;
        try {
            $property = static::newQuery($this->getFullPath() . "/properties/$key")
                ->setModel(Property::class)
                ->get();

            $property->setPath($this->getFullPath() . '/properties');
        } catch (Exception $e) {
            // TODO: Log error
        }
        return $property;
    }

//    public function getProperty(string $key): ?Property
//    {
//        $property = $this->findProperty(['key' => $key]);
//
//        return $property?->setPath($this->getFullPath() . '/properties');
//    }

    public function getOrAddProperty(string $key, string $value): Property
    {
        $property = $this->getProperty($key);

        if ($property) {
            // Return existing if value is the same
            if ($property->value != $value) {
                // Update value of existing and return it
                $property->update(['value' => $value]);
            }
            return $property->setPath($this->getFullPath() . '/properties');
        } else {
            // Add new
            return $this->addProperty($key, $value);
        }
    }

    public function hasProperty(array $attributes, string $when = 'now'): bool
    {
        return (bool)$this->findProperty($attributes, $when);
    }

    public function copyProperty(Property $source, Customer $sourceCustomer, bool $verbose = false): ?Property
    {
        if (!$this->hasProperty(['key' => $source->key])) {
            // Handle dynamic properties -------------------------------------------------------------------------------
            $property_key = $source->key;
            $property_value = $source->value;

            $dynamic = false;
            $skip = false;

            switch ($property_key) {
                case 'legacy_id':
                case 'learningbank.id':
                case 'scheduling.last_swap_notification':
                case 'src.last_absence_sync':
                case 'src.last_assignment_sync':
                case 'src.last_employee_sync':
                case 'src.last_family_sync':
                case 'src.last_fiscal_sync':
                case 'src.last_remuneration_sync':
                case 'ai-budgeting-ml-bucket':
                    $skip = true;
                    break;

                case 'element-suite.restaurant_id':
                    $dynamic = true;
                    $property_value = '20' . $this->number;
                    break;

                case 'general:parent_company_name':
                    $dynamic = true;
                    $property_value = $this->origin()->name ?? 'not found';
                    break;

                case 'general:parent_company_organization_number':
                    $dynamic = true;
                    $property_value = $this->origin()->organization_number ?? 'not found';
                    break;

                case 'general:parent_company_address1':
                    $dynamic = true;
                    $property_value = $this->origin()->address1 ?? 'not found';
                    break;

                case 'general:parent_company_postal_code':
                    $dynamic = true;
                    $property_value = $this->origin()->postal_code ?? 'not found';
                    break;

                case 'general:parent_company_city':
                    $dynamic = true;
                    $property_value = $this->origin()->city ?? 'not found';
                    break;

                case 'fhevo.default_group_id':
                    $dynamic = true;
                    $property_value = $this->findUserGroup(['name' => $sourceCustomer->getUserGroup($source->value)->name])->id ?? 'not found';
                    break;

                case 'fhevo.id':
                    $property_value = 'value not set';
                    break;

                case 'schedule_auditor_group_id':
                    $dynamic = true;
                    $new_group_array = [];
                    $oldGroup = json_decode($property_value);
                    if (is_array($oldGroup)) {
                        foreach ($oldGroup as $group_id) {
                            $new_group_id = $this->findUserGroup(['name' => $sourceCustomer->getUserGroup($group_id)->name])->id;
                            if (!is_null($new_group_id)) {
                                $new_group_array[] = $new_group_id;
                            }
                        }
                        if (empty($new_group_array)) {
                            // This operation failed
                            $property_value = 'value could not be set automatically'; // most likely because the source ids did not match that of the customer's
                        } else {
                            $property_value = json_encode($new_group_array);
                        }
                    } else {
                        $new_group_id = $this->findUserGroup(['name' => $sourceCustomer->getUserGroup($oldGroup)->name])->id;

                        if (is_null($new_group_id)) {
                            // This operation failed
                            $property_value = 'value could not be set automatically'; // most likely because the source ids did not match that of the customer's
                        } else {
                            $property_value = $new_group_id;
                        }
                    }

                    break;

                default:
                    // Handle default report overrides
                    if (substr($property_key, 0, 8) == 'reports.' && substr($property_key, -10) == '.overrides')
                    {
                        $usesId = abs((int)filter_var($property_key, FILTER_SANITIZE_NUMBER_INT));
                        if ($usesId !== 0)
                        {
                            $dynamic = true;
                            $reportClass = $this->getReport($usesId)->class ?? 'unknown';
                            $property_key = "reports.{report.class=$reportClass}.overrides";
                        }
                    }
                    break;
            }
            // ---------------------------------------------------------------------------------------------------------

            if ($skip) {
//                addToLog($log, "\tskipping property [" . $property['key'] . "]");
                if ($verbose) {
                    logg()->info('{dgreen}' . '>');
                }
            } else {
                $newProperty = Property::newInstance(
                    array_filter([
                        'key' => $property_key,
                        'value' => $property_value,
                        'from' => $source->from,
                        'to' => $source->to
                    ], static function ($var) {
                        return $var !== null;
                    }
                    ))->setPath($this->getFullPath() . '/properties');

                $newProperty->save();

                if ($dynamic) {
                    if ($verbose) {
                        logg()->info('{dgreen}' . '!');
                    }
                } else {
                    if ($verbose) {
                        logg()->info('{dgreen}' . '+');
                    }
                }

                return $newProperty;
            }
        }
        return null;
    }

    public function addProperty(string $key, string $value, ?string $from = null, ?string $to = null): ?Property
    {
        if (!$this->hasProperty(['key' => $key])) {
            $newProperty = Property::newInstance(
                array_filter([
                    'key' => $key,
                    'value' => $value,
                    'from' => $from,
                    'to' => $to
                ], static function ($var) {
                    return $var !== null;
                }
                ))->setPath($this->getFullPath() . '/properties');

            $newProperty->save();

            return $newProperty;
        }
        return null;
    }

    public function updateProperty(string $key, string $newValue): bool
    {
        $property = $this->getProperty($key);

        if (is_null($property)) {
            return false;
        }

        // Return true if value is the same TODO: This should have been handled in ->save()
        if ($property->value == $newValue) {
            return true;
        }

        if ($this->hasPropertyController ?? true) {
            return $property->update(['value' => $newValue]);
        } else {
            eaw()->update($this->getFullPath() . "/properties/$key", null, ['value' => $newValue]);
            return true;
        }
    }

    public function updateOrAddProperty(string $key, string $newValue): bool
    {
        // Check if property exists
        $property = $this->getProperty($key);

        // Add property and set its value if it doesn't exist
        if (is_null($property)) {
            return (bool)$this->addProperty($key, $newValue);
        }

        // Return true if value is the same TODO: This should have been handled in ->save()
        if ($property->value == $newValue) {
            return true;
        }

        // Value differs, so update existing
        if ($this->hasPropertyController ?? true) {
            return $property->update(['value' => $newValue]);
        } else {
            eaw()->update($this->getFullPath() . "/properties/$key", null, ['value' => $newValue]);
            return true;
        }
    }

    public function removeProperty(Property $property): bool
    {
        try {
            eaw()->delete($this->getFullPath() . "/properties/$property->id");

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function resolveProperty(string $key): ?string
    {
        return $this->getProperty($key)?->value;
    }

    public function test()
    {
        foreach (SettingGroup::get(3330)->members() as $member) {
            $customer = Customer::get($member->id);
            $ptls = $customer->payTypeLinks();
            echo "$customer->id\t$customer->name\t";
            foreach ($ptls as $ptl) {
                if ($ptl->hasProperty(['key' => 'overtime.only_day_before_holiday', 'value' => '1'])) {
                    echo $ptl->getOrAddProperty('overtime.only_holidays',
                        '["norway/maundy-thursday"]') ? '.' : $ptl->id;
                    echo $ptl->getOrAddProperty('overtime.start_time', '12:00') ? '.' : $ptl->id;
                }
            }
            echo PHP_EOL;
        }
    }
}
