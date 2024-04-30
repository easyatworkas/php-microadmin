<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\CustomerPosition;

trait HasCustomerPositions
{
    public function positions(): array
    {
        return static::newQuery($this->getFullPath() . '/positions')
            ->setModel(CustomerPosition::class)
            ->getAll()
            ->all();
    }

    public function getPosition(string $key): ?CustomerPosition
    {
        $position = null;
        try {
            $position = static::newQuery($this->getFullPath() . "/positions/$key")
                ->setModel(CustomerPosition::class)
                ->get();
        } catch (\Exception $e) {
            // TODO: Log error
        }
        return $position;
    }

    public function hasPosition(array $attributes): bool
    {
        return (bool)$this->findPosition($attributes);
    }

    public function findPosition(array $parameters): ?CustomerPosition
    {
        foreach ($this->positions() as $position) {
            if ($position->matches($parameters)) {
                return $position;
            }
        }
        return null;
    }

    public function copyPosition(CustomerPosition $source): ?CustomerPosition
    {
        if (!$this->hasPosition([ 'name' => $source->name ])) {

            // Add new Position
            return $this->addPosition($source->name);
        }
        return null;
    }

    public function addPosition(string $name): ?CustomerPosition
    {
        // Create new Position
        $newPosition = CustomerPosition::newInstance([
            'name' => $name
        ])->setPath("/customers/$this->id/positions");
        $newPosition->save();

        return $newPosition;
    }
}