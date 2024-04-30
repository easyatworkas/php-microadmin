<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

// TODO: MeController, SsoController, TosController, BadgeController, PeopleController, RegionController,
// TODO: BalanceController, CountryController, LanguageController, HolidayController, FsController, ChangelogController,
// TODO: FileController, SystemAlertController, NotificationController, OpeningHoursController, FormController,
// TODO: NodeAclController, LinkController, OrganizationController, MetricController

namespace Ext\Models;

use Eaw\Models\Model as BaseModel;

/**
 * @property mixed|null $id
 */
class Model extends BaseModel
{
    public $keyName = 'id';
    /**
     * // Use the controller to gather an array of (all) Models --------------------------------------------------------
     * public function somethings(): array
     *
     * // Get a (related) Model by its Key -----------------------------------------------------------------------------
     * public function getSomething(string $key): ?Something
       {
          foreach ($this->somethings() as $model) {
             if ($model->{$model->keyName} == $key) {
                return $model;
             }
          }
          return null;
       }
     *
     * // Returns one/first instance of a Model matching the specified attributes (and time) ---------------------------
     * public function findSomething( array $attributes = [], string $when = 'now' ): ?Something                        // When there can be multiple, but only one is active
     * public function findSomething( array $attributes, string $when = 'now' ): ?Something                             // When there can be multiple
     * public function findSomething( array $attributes ): ?Something                                                   // When there can only be one
     *
     * // Returns an array of all Model instances matching the specified attributes (within a time window) -------------
     * public function findSomethings( array $attributes = [], string $from = null, string $to = null ): array
     *
     * // Returns a bool if an instance of a Model matching the specified attributes (and time) exists -----------------
     * public function hasSomething( array $attributes = [], string $when = 'now' ): bool                               // When there can be multiple, but only one is active
     * public function hasSomething( array $attributes, string $when = 'now' ): bool                                    // When there can be multiple
     * public function hasSomething( array $attributes ): bool                                                          // When there can only be one
     *
     * // Create a copy of a source Model, and return it if successful -------------------------------------------------
     * public function copySomething( Something $source ): ?Something
     *
     * // Create or add a (new) Model with the provided parameters, and return it if successful ------------------------
     * public function newSomething( $param1, $param2, .. ): ?Something                                                 // When there can be multiple, but only one is active
     * public function addSomething( $param1, $param2, .. ): ?Something                                                 // When there can be multiple
     * public function setSomething( $param1, $param2, .. ): ?Something                                                 // When there can only be one
     *
     */

    protected ?Model $owner = null;

    public function setOwner(Model $model): void
    {
        $this->owner = $model;
    }

    public function getOwner(): ?Model
    {
        return $this->owner;
    }

    public function getParent(): ?Model
    {
        $splitPath = explode('/', $this->getFullPath());

        // Return null if Model has no Parent
        if (count($splitPath) <= 3) return null;

        // Parent
        $parentType = $splitPath[ count($splitPath) - 4 ];
        $parentId = $splitPath[ count($splitPath) - 3 ];
        $parentPath = "";
        for ($i = 1; $i <= count($splitPath)-3; $i++)
        {
            $parentPath .= '/' . $splitPath[$i];
        }

        switch ( $parentType )
        {
            case 'customers':
                return Customer::get($parentId);
            case 'setting_groups':
                return SettingGroup::get($parentId);
            case 'customer_groups':
                return CustomerGroup::get($parentId);
            case 'tariffs':
                return SettingGroupTariff::get($parentId);
            case 'users':
                return User::get($parentId);
            case 'user_groups':
                return UserGroup::get($parentId);
            case 'permission_sets':
                return PermissionSet::get($parentId);
            case 'default_hr_files':
                return eaw()->query($parentPath)->setModel(CustomerDefaultHrFile::class)->get();
            case 'hr_files':
                return eaw()->query($parentPath)->setModel(EmployeeHrFile::class)->get();
            case 'schedules':
                return eaw()->query($parentPath)->setModel(CustomerSchedule::class)->get();
            case 'shifts':
                return eaw()->query($parentPath)->setModel(CustomerShift::class)->get();
            case 'periods':
                return eaw()->query($parentPath)->setModel(ShiftPeriod::class)->get();
            case 'employees':
                return eaw()->query($parentPath)->setModel(Employee::class)->get();
        }

        // No matching Parent
        return null;
    }

    public function getIdOf(string $model): ?string
    {
        $splitPath = explode('/', $this->getFullPath());

        for ($i = 1; $i <= count($splitPath)-1; $i++)
        {
            if (substr($splitPath[$i], 0, mb_strlen($model)) == $model) return $splitPath[$i+1];
        }
        return null;
    }

    public function matches(array $parameters): bool
    {
        foreach ($parameters as $key => $values) {
            if (is_array($values)) {
                foreach ($values as $value) {
                    if ($this->matches(array_merge($parameters, [ $key => $value ]))) {
                        return true;
                    }
                }
            }
        }

        $toCheck = array_intersect_key($this->getAttributes(), $parameters);

        return $toCheck == $parameters;
    }

    public function isActive(string $when = 'now', bool $allowOverlapWithFrom = true, bool $allowOverlapWithTo = true): bool
    {
        // Always return true if Model has no 'from' and 'to' attributes
        if (!$this->hasAttribute('from')) return true;

        $from = $this->getAttribute('from');
        $to = $this->getAttribute('to');

        // Always return true if $from is null
        if (is_null($from)) return true;

        try {
            $whenDateTime = new \DateTime($when);
            $fromDateTime = new \DateTime($from);

            // If open to-date only compare with start
            if (is_null($to))
            {
                return $whenDateTime >= $fromDateTime;
            }
            else
            {
                $toDateTime = new \DateTime($to);

                $startWithin = $allowOverlapWithFrom ? $whenDateTime >= $fromDateTime : $whenDateTime > $fromDateTime;
                $endWithin = $allowOverlapWithTo ? $whenDateTime <= $toDateTime : $whenDateTime < $toDateTime;

                return $startWithin && $endWithin;
            }

        } catch (\Exception $e) {
            logg()->info($GLOBALS['error']."$when is not a valid DateTime\n");
            return false;
        }
    }

    public function isWithin(string $compareFrom, ?string $compareTo): bool
    {
        // Always return true if Model has no 'from' and 'to' attributes
        if (!$this->hasAttribute('from')) return true;

        $from = $this->getAttribute('from');
        $to = $this->getAttribute('to');

        // Always return true if $from is null
        if (is_null($from)) return true;

        // Open to-date -- only compare with start
        if (is_null($compareTo)) return $this->isActive($compareFrom);

        // Returns true only if both $from and $to are within the provided interval
        return $this->isActive($compareFrom) && $this->isActive($compareTo);
    }

    public function overlaps(string $compareFrom, ?string $compareTo): bool
    {
        // Always return true if Model has no 'from' and 'to' attributes
        if (!$this->hasAttribute('from')) return true;

        $from = $this->getAttribute('from');
        $to = $this->getAttribute('to');

        // Always return true if $from is null
        if (is_null($from)) return true;

        // Open to-date -- only compare with start
        if (is_null($compareTo)) return $this->isActive($compareFrom);

        // Returns true if either $from or $to is within the provided interval
        return $this->isActive($compareFrom, true, false) || $this->isActive($compareTo, false, true);
    }

    public function resolveSetting(string $key): ?string
    {
        $value = null;

        $class = trim($this::class, "Ext\Models\\");

        $value = match ($class) {
            'SettingGroup' => $this->parent() ? $this->parent()->resolveSetting($key) : $value,
            'Customer' => $this->settingGroup()->resolveSetting($key),
            default => Customer::get($this->getIdOf('customer'))->resolveSetting($key),
        };

        return $this->hasProperty(['key' => $key]) ? $this->getProperty($key)->value : $value;
    }
}
