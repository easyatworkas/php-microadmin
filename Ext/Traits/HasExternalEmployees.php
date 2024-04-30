<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\Customer;
use Ext\Models\EmployeeContract;
use Ext\Models\Employee;
use Ext\Models\EmployeeExternalEmployee;
use Ext\Models\EmployeePayRate;
use Ext\Models\Property;
use Ext\Models\UserGroup;

trait HasExternalEmployees
{
    public function externalEmployees(): array
    {
        return $this->client->query($this->getFullPath() . '/external_employees')
                ->setModel(EmployeeExternalEmployee::class)
                ->includeInactive(true)
                ->includeFuture(true)
                ->getAll()
                ->all();
    }

    public function getExternalEmployee(string $key): ?Employee
    {
        foreach ($this->externalEmployees() as $model) {
            if ($model->id == $key) {
                return $model;
            }
        }
        return null;
    }

    public function findExternalEmployee(array $attributes, string $when = 'now'): ?EmployeeExternalEmployee
    {
        foreach ($this->externalEmployees() as $model) {
            if ($model->matches($attributes)) {
                if (dateTimeWithin($when, $model->from, $model->to)) {
                    return $model;
                }
            }
        }
        return null;
    }

    public function findExteralEmployees(array $attributes, string $from = null, string $to = null, bool $strict = false): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($from) && is_null($to)) {
            return $this->externalEmployees();
        }

        $found = [];

        foreach ($this->externalEmployees() as $model) {
            if ($model->matches($attributes)) {
                if (intervalWithin($from, $to, $model->from, $model->to, $strict)) {
                    $found[] = $model;
                }
            }
        }
        return $found;
    }

    public function hasExternalEmployee(array $attributes, string $when = 'now'): bool
    {
        return (bool)$this->findExternalEmployee($attributes, $when);
    }

    public function copyExternalEmployee(EmployeeExternalEmployee $source, bool $addUser = true): ?EmployeeExternalEmployee
    {
        if (!$this->hasExternalEmployee([ 'employee_id' => $source->employee_id ])) {
            $user = Employee::customer($source->customer_id)->get($source->employee_id)->user();

            // Create new Employee
            if ($addUser && !is_null($user)) {

                // Create User Access
                $this->addUserAccess($user);

                $newEmployee = Employee::newInstance([
                    'user_id' => $source->user_id,
                    'email' => $source->email,
                    'phone' => $source->phone,
                    'birth_date' => $source->birth_date,
                    'gender' => $source->gender,
                    'address1' => $source->address1,
                    'address2' => $source->address2,
                    'postal_code' => $source->postal_code,
                    'city' => $source->city,
                    'country' => $source->country,
                    'number' => $source->number,
                    'from' => $source->from,
                    'to' => $source->to,
                    'extra_seniority' => $source->extra_seniority,
                    'country_code' => $source->country_code,
                    'nationality' => $source->nationality,
                    'region_id' => $source->region_id
                ])->setPath("/customers/$this->id/employees");
            } else {
                $newEmployee = Employee::newInstance([
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $source->email,
                    'phone' => $source->phone,
                    'birth_date' => $source->birth_date,
                    'gender' => $source->gender,
                    'address1' => $source->address1,
                    'address2' => $source->address2,
                    'postal_code' => $source->postal_code,
                    'city' => $source->city,
                    'country' => $source->country,
                    'number' => $source->number,
                    'from' => $source->from,
                    'to' => $source->to,
                    'extra_seniority' => $source->extra_seniority,
                    'country_code' => $source->country_code,
                    'nationality' => $source->nationality,
                    'region_id' => $source->region_id
                ])->setPath("/customers/$this->id/employees");
            }

            // Save
            $newEmployee->save();

            return $newEmployee;
        }
        return null;
    }

    // TODO:
    public function addEmployee($param1, $param2): ?Employee
    {
        throw new Exception('Not implemented');
    }


    // TODO: also create for "lent" employees
//    protected ?array $myExternalEmployees = null;
//    public function externalEmployees(): array
//    {
//        return !is_null($this->myExternalEmployees)
//            ? $this->myExternalEmployees
//            : $this->myExternalEmployees = $this->client->query($this->getFullPath() . '/external_employees')->setModel(EmployeeExternalEmployee::class)->getAll()->all();
//    }
    public function addExternalEmployee(Employee $employee, bool $addUser, ?UserGroup $userGroup, string $fromBd, string $toBd = null): bool
    {
        try
        {
            // Create External Employee
            eaw()->create("/customers/$this->id/external_employees", null, array_filter([
                'employee_id' => $employee->id,
                'from' => $fromBd,
                'to' => $toBd,
            ], static function($var) {return $var !== null;}));

            if ($addUser)
            {
                // Create User Access
                $this->addUserAccess($employee->user(), toNowOrFuture($fromBd));

                // Create User Group Membership
                $userGroup->addMember($employee->user(), utcDateTimeString($fromBd));
            }
            return true;
        }
        catch (Exception $e)
        {
            return false;
        }
    }
}