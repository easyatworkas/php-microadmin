<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\CustomerRotation;

trait HasCustomerRotations
{
    protected ?array $myRotations = null;

    public function rotations(): array
    {
        return !is_null($this->myRotations)
            ? $this->myRotations
            : $this->myRotations = $this->client->query($this->getFullPath() . '/rotations')
                ->setModel(CustomerRotation::class)
                ->with([ 'employees' ])
                ->getAll()
                ->all();
    }

    public function getRotation(string $key): ?CustomerRotation
    {
        foreach ($this->rotations() as $rotation) {
            if ($rotation->id == $key) {
                return $rotation;
            }
        }
        return null;
    }

    public function findRotation(array $attributes): ?CustomerRotation
    {
        foreach ($this->rotations() as $rotation) {
            if ($rotation->matches($attributes)) return $rotation;
        }
        return null;
    }

    public function findRotations(array $attributes): array
    {
        // If no params provided, return all
        if (empty($attributes)) {
            return $this->rotations();
        }

        $foundRotations = [];

        foreach ($this->rotations() as $rotation) {
            if ($rotation->matches($attributes)) $foundRotations[] = $rotation;
        }
        return $foundRotations;
    }

    public function hasRotation(array $attributes): bool
    {
        return (bool)$this->findRotation($attributes);
    }

    public function copyRotation(CustomerRotation $source): ?CustomerRotation
    {
        return $this->addRotation($source->name, $source->start_day, $source->days);
    }

    public function addRotation(string $name, string $startDay, int $days): ?CustomerRotation
    {
        try {
            $rotation = CustomerRotation::newInstance([
                'name' => $name,
                'start_day' => $startDay,
                'days' => $days,
            ])->setPath("/customers/$this->id/rotations");

            $rotation->save();

            return $rotation;
        } catch (Exception $e) {
            return null;
        }
    }
}