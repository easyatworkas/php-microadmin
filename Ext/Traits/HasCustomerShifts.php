<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\CustomerShift;

trait HasCustomerShifts
{
    public function shifts(): array
    {
        if (!$this->hasProduct("Scheduling")) return [];

        return $this->client->query($this->getFullPath() . '/shifts')
            ->setModel(CustomerShift::class)
            ->getAll()
            ->all();
    }

    public function findShift(array $attributes = []): ?CustomerShift
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

        $foundShifts = [];
        $start = is_null($from) ? $this->created_at : $from;
        $end = $to;

        foreach ($this->shifts() as $shift) {
            if ($shift->matches($attributes)) {
                if (intervalWithin($shift->from, $shift->to, $start, $end)) {
                    $foundShifts[] = $shift;
                }
            }
        }
        return $foundShifts;
    }

    public function getShift(string $key): ?CustomerShift
    {
        $shift = null;
        try {
            $shift = static::newQuery($this->getFullPath() . "/shifts/$key")
                ->setModel(CustomerShift::class)
                ->get();
        } catch (\Exception $e) {
            // TODO: Log error
        }
        return $shift;
    }

    public function hasShift(array $attributes = [], string $when = 'now'): bool
    {
        return (bool)$this->findShift($attributes, $when);
    }

    public function copyShift(CustomerShift $source): ?CustomerShift
    {
        throw new \Exception('Not implemented');
    }
}