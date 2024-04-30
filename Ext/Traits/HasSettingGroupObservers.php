<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\SettingGroupObserver;

trait HasSettingGroupObservers
{
    public function observers(): array
    {
        return $this->client->query($this->getFullPath() . '/observers')
            ->setModel(SettingGroupObserver::class)
            ->getAll()
            ->all();
    }
    public function getObserver(string $key): ?SettingGroupObserver
    {
        foreach ($this->observers() as $model)
        {
            if ($model->{$model->keyName} == $key) {
                return $model;
            }
        }
        return null;
    }

    public function findObserver(array $attributes, ?string $when = 'now'): ?SettingGroupObserver
    {
        foreach ($this->observers() as $model) {
            if ($model->matches($attributes)) {
                if (dateTimeWithin($when, $model->from, $model->to)) {
                    return $model;
                }
            }
        }
        return null;
    }

    public function findObservers(array $attributes, string $from = null, string $to = null): array
    {
        $observers = [];
        foreach ($this->observers() as $model) {
            if ($model->matches($attributes)) {
                if (dateTimeWithin($from, $model->from, $model->to)) {
                    $observers[] = $model;
                }
            }
        }
        return $observers;
    }

    public function hasObserver(array $attributes = [], string $when = 'now'): bool
    {
        return (bool)$this->findObserver($attributes, $when);
    }

    public function addObserver(string $class, string $from = 'now', $to = null): ?SettingGroupObserver
    {
        if (!$this->hasObserver(['class' => $class]))
        {
            try {
                $newObserver = SettingGroupObserver::newInstance(array_filter(
                    [
                        'class' => $class,
                        'from' =>  toNowOrFuture($from),
                        'to' => $to,
                    ],
                    static function($var) { return $var !== null; }
                ))->setPath("/setting_groups/{$this->id}/observers");

                $newObserver->save();

                return $newObserver;

            } catch (\Exception $e) {

                // TODO: Proper Logging
                return null;
            }
        }
        return null;
    }

    public function copyObserver(SettingGroupObserver $observer): ?SettingGroupObserver
    {
        if (!$this->hasObserver(['class' => $observer->class]))
        {
            $newObserver = $this->addObserver($observer->class, $observer->from, $observer->to);

            // Return null on error
            if (!$newObserver) {
                return null;
            }

            // Copy Properties
            foreach ($observer->properties() as $property)
            {
                $newObserver->addProperty($property->key, $property->value, $property->from, $property->to);
            }

            // Return the new observer
            return $newObserver;
        }
        return null;
    }
}