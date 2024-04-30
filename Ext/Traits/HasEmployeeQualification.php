<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\CustomerQualification;
use Ext\Models\EmployeeQualification;

trait HasEmployeeQualification
{
    public function qualifications(): array
    {
        return $this->client->query($this->getFullPath() . '/qualifications')
            ->setModel(EmployeeQualification::class)
            ->getAll()
            ->all();
    }

    // TODO: This is not working for some reason
    public function addQualification(CustomerQualification $qualification, string $from = 'now', float $rate = 1, string $to = null): ?EmployeeQualification
    {
        echo "CustomerQualification: $qualification->id: $qualification->customer_id\n";
        echo "From: " . ($from == 'now' ? utcNow('Y-m-d') : $from) . "\n";
        //try {
            $employeeQualification = EmployeeQualification::newInstance(array_filter([
                'id' => $qualification->id,
                'from' => $from == 'now' ? utcNow('Y-m-d') : $from,
                'rate' => $rate,
                'to' => $to,
            ], static function($var) {return $var !== null;} ))
                ->setPath("/customers/$qualification->customer_id/employees/$this->id/qualifications");

            $employeeQualification->save();

            return $employeeQualification;
//        } catch (\Exception $e) {
//            logg()->info($e->getCode() . ' - ' . $e->getMessage());
//            return null;
//        }

    }
}