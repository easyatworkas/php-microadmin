<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\CustomerReport;

trait HasCustomerReports
{
    public function reports(): array
    {
        if (!$this->hasProduct("Reports")) {
            return [];
        }

        return static::newQuery($this->getFullPath() . '/reports')
            ->setModel(CustomerReport::class)
            ->getAll()
            ->all();
    }

    public function getReport(string $key): ?CustomerReport
    {
        $report = null;
        try {
            $report = static::newQuery($this->getFullPath() . "/reports/$key")
                ->setModel(CustomerReport::class)
                ->get();
        } catch (\Exception $e) {
            // TODO: Log error
        }
        return $report;
    }

    public function findReport(array $attributes): ?CustomerReport
    {
        foreach ($this->reports() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function hasReport(array $attributes): bool
    {
        return (bool)$this->findReport($attributes);
    }

    public function addReport(string $class): ?CustomerReport
    {
        // Return existing Report if it already exists
        $existing = $this->findReport(['class' => $class]);
        if ($existing) {
            return $existing;
        }

        // Create new Report instance
        try {
            $model = CustomerReport::newInstance([
                'class' => $class,
            ])->setPath("/customers/$this->id/reports");

            $model->save();

            return $model;
        } catch (\Exception $e) {
            // TODO: Log error
            return null;
        }
    }

    public function removeReport(string $class): bool
    {
        // Return false if report not found
        $existing = $this->findReport(['class' => $class]);
        if (!$existing) {
            return false;
        }

        try {
            return $existing->delete();
        } catch (Exception $e) {
            // TODO: Log error
            return false;
        }
    }

    public function copyReport(CustomerReport $source): ?CustomerReport
    {
        return $this->addReport($source->class);
    }
}