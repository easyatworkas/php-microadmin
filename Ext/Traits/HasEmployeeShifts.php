<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\EmployeeShift;

trait HasEmployeeShifts
{
    protected ?array $myShifts = null;
    protected ?array $myPublishedShifts = null;
    protected ?array $myUnpublishedShifts = null;

    public function shifts(): array
    {
        return array_merge($this->publishedShifts(), $this->unpublishedShifts());
    }

    public function publishedShifts(): array
    {
        return !is_null($this->myPublishedShifts)
            ? $this->myPublishedShifts
            : $this->myPublishedShifts = $this->client->query($this->getFullPath() . '/shifts')
                ->setModel(EmployeeShift::class)
                ->published(true)
                ->getAll()
                ->all();
    }

    public function unpublishedShifts(): array
    {
        return !is_null($this->myUnpublishedShifts)
            ? $this->myUnpublishedShifts
            : $this->myUnpublishedShifts = $this->client->query($this->getFullPath() . '/shifts')
                ->setModel(EmployeeShift::class)
                ->published(false)
                ->getAll()
                ->all();
    }

//    public function findShift(array $attributes = [], string $when = 'now'): ?EmployeeShift
//    {
//        foreach ($this->shifts() as $shift) {
//            if ($shift->matches($attributes)) {
//                if (dateTimeWithin($when, $shift->from, $shift->to)) {
//                    return $shift;
//                }
//            }
//        }
//        return null;
//    }

    public function findShift(array $attributes = []): ?EmployeeShift
    {
        foreach ($this->shifts() as $shift) {
            if ($shift->matches($attributes)) {
                return $shift;
            }
        }
        return null;
    }

    public function findShifts(array $attributes = [], string $from = null, string $to = null): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($from) && is_null($to)) {
            return $this->shifts();
        }

        $found = [];
        $start = is_null($from) ? $this->from : $from;
        $end = $to;

        foreach ($this->shifts() as $model) {
            if ($model->matches($attributes)) {
                if (intervalWithin($model->from, $model->to, $start, $end)) {
                    $found[] = $model;
                }
            }
        }
        return $found;
    }

    public function getShift(string $key): ?EmployeeShift
    {
        return $this->findShift(['id' => $key]);
    }

    public function hasShift(array $attributes = [], string $when = 'now'): bool
    {
        return (bool)$this->findShift($attributes, $when);
    }

    public function copyShift(EmployeeShift $source): ?EmployeeShift
    {
        throw new Exception('Not implemented');
    }
}