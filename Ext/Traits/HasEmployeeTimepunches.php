<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\EmployeeTimepunch;

trait HasEmployeeTimepunches
{
    public function newTimepunch(string $in, ?string $out, ?string $businessDate = null, ?int $businessUnitId = null, ?string $comment = null): ?EmployeeTimepunch
    {
        try {
            $timepunch = EmployeeTimepunch::newInstance(array_filter([
                'in' => $in,
                'out' => $out,
                'business_date' => $businessDate,
                'business_unit_id' => $businessUnitId,
                'comment' => $comment,
            ], static function ($var) {
                return $var !== null;
            }))->setPath("/customers/$this->customer_id/employees/$this->id/timepunches");

            $timepunch->save();

            return $timepunch;
        } catch (Exception $e) {
            return null;
        }
    }

    public function activeTimepunch(): ?EmployeeTimepunch
    {
        try {
            return EmployeeTimepunch::newQuery($this->getFullPath() . '/timepunches/active')->get()->setPath($this->getFullPath() .'/timepunches');
        } catch (Exception $e) {
            return null;
        }
    }

    public function isPunchedIn(): bool
    {
        return (bool)$this->activeTimepunch();
    }

    public function punchIn(?string $comment = null, ?int $businessUnitId = null): bool
    {
        if ($this->isPunchedIn()) {
            return false;
        }

        return !is_null($this->newTimepunch('now', null, null, $businessUnitId, $comment));
    }

    public function punchOut(?string $comment = null): bool
    {
        if (!$this->isPunchedIn()) {
            return false;
        }

        return $this->activeTimepunch()->update(array_filter([
            'out' => 'now',
            'comment' => $comment
        ], static function ($var) { return $var !== null; }));
    }
}