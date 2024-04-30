<?php // TODO: Same as QualifiedEmployee?

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\EmployeeQualification;
use Ext\Models\QualifiedEmployee;

trait HasQualifiedEmployees
{
    public function qualifiedEmployees(string $when = 'now'): array
    {
        return $this->client->query($this->getFullPath() . '/employees')
            ->setModel(QualifiedEmployee::class)
            ->getAll()
            ->all();
    }

    public function employeeQualifications(string $when = 'now'): array
    {
        return $this->client->query($this->getFullPath() . '/employees')
            ->setModel(EmployeeQualification::class)
            ->getAll()
            ->all();
    }
}