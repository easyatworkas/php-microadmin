<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\CustomerQualification;

trait HasCustomerQualifications
{
    public function qualifications(): array
    {
        return static::newQuery($this->getFullPath() . '/qualifications')
            ->setModel(CustomerQualification::class)
            ->getAll()
            ->all();
    }

    public function getQualification(string $key): ?CustomerQualification
    {
        $qualification = null;
        try {
            $qualification = static::newQuery($this->getFullPath() . "/qualifications/$key")
                ->setModel(CustomerQualification::class)
                ->get();
        } catch (\Exception $e) {
            // TODO: Log error
        }
        return $qualification;
    }

    public function findQualification(array $attributes): ?CustomerQualification
    {
        foreach ($this->qualifications() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function findQualifications(array $attributes): array
    {
        // If no params provided, return all
        if (empty($attributes)) {
            return $this->qualifications();
        }

        $found = [];

        foreach ($this->qualifications() as $model) {
            if ($model->matches($attributes)) {
                $found[] = $model;
            }
        }
        return $found;
    }

    public function hasQualification(array $attributes): bool
    {
        return (bool)$this->findQualification($attributes);
    }

    public function addQualification(string $name): ?CustomerQualification
    {
        if (!$this->hasQualification([ 'name' => $name ]))
        {
            $model = CustomerQualification::newInstance(array_filter([
                'name' => $name,
            ], static function ($var) {
                return $var !== null;
            }))->setPath("/customers/$this->id/qualifications");

            $model->save();

            return $model;
        }
        return null;
    }

    /**
     * @deprecated Replaced by findQualification or hasQualification
     * @param array $parameters
     *
     * @return \Ext\Models\CustomerQualification|null
     */
    public function hasQualificationWith( array $parameters ): ?CustomerQualification
    {
        foreach ($this->qualifications() as $qualification)
        {
            if ($qualification->matches($parameters))
            {
                return $qualification;
            }
        }
        return null;
    }
}