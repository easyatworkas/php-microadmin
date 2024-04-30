<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\CustomerSchedule;

trait HasCustomerSchedules
{
    public function schedules(): array
    {
        if (!$this->hasProduct('Scheduling')) return [];

        return static::newQuery($this->getFullPath() . '/schedules')
            ->setModel(CustomerSchedule::class)
            ->getAll()
            ->all();
    }

    public function templates(): array
    {
        return static::newQuery($this->getFullPath() . '/schedules')
            ->setModel(CustomerSchedule::class)
            ->template(true)
            ->getAll()
            ->all();
    }

    public function getSchedule(string $key): ?CustomerSchedule
    {
        $schedule = null;
        try {
            $schedule = static::newQuery($this->getFullPath() . "/schedules/$key")
                ->setModel(CustomerSchedule::class)
                ->get();
        } catch (Exception $e) {
            // TODO: Log error
        }
        return $schedule;
    }

    public function getScheduleOrTemplate(string $key): ?CustomerSchedule
    {
        return $this->getSchedule($key) ?? $this->getTemplate($key);
    }

    public function getTemplate(string $key): ?CustomerSchedule
    {
        $schedule = null;
        try {
            $schedule = static::newQuery($this->getFullPath() . "/schedules/$key")
                ->setModel(CustomerSchedule::class)
                ->template(true)
                ->get();
        } catch (Exception $e) {
            // TODO: Log error
        }
        return $schedule;
    }

    public function findSchedule(array $attributes): ?CustomerSchedule
    {
        foreach ($this->schedules() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function findTemplate(array $attributes): ?CustomerSchedule
    {
        foreach ($this->templates() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function findSchedules(array $attributes): array
    {
        // If no params provided, return all
        if (empty($attributes)) {
            return $this->schedules();
        }

        $found = [];

        foreach ($this->schedules() as $model) {
            if ($model->matches($attributes)) {
                $found[] = $model;
            }
        }
        return $found;
    }

    public function findTemplates(array $attributes): array
    {
        // If no params provided, return all
        if (empty($attributes)) {
            return $this->templates();
        }

        $found = [];

        foreach ($this->templates() as $model) {
            if ($model->matches($attributes)) {
                $found[] = $model;
            }
        }
        return $found;
    }

    public function hasSchedule(array $attributes): bool
    {
        return (bool)$this->findSchedule($attributes);
    }

    public function hasTemplate(array $attributes): bool
    {
        return (bool)$this->findTemplate($attributes);
    }


    public function addSchedule(string $name): ?CustomerSchedule // TODO: Not done
    {
        if (!$this->hasSchedule(['name' => $name]))
        {
            $model = CustomerSchedule::newInstance(array_filter([
                'name' => $name,
            ], static function ($var) {
                return $var !== null;
            }))->setPath("/customers/$this->id/schedules");

            $model->save();

            return $model;
        }
        return null;
    }

    public function copySchedule(CustomerSchedule $source, bool $includeShifts = true): ?CustomerSchedule // TODO: Not done
    {
        if (!$this->hasSchedule(['name' => $source->name]))
        {
            $model = CustomerSchedule::newInstance(array_filter([
                'name' => $source->name,
            ], static function ($var) {
                return $var !== null;
            }))->setPath("/customers/$this->id/schedules");

            $model->save();

            if ($includeShifts) {
                foreach ($source->shifts() as $shift) {
                    $model->addShift($shift);
                }
            }

            return $model;
        }
        return null;
    }
}