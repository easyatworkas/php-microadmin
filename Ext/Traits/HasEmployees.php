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

trait HasEmployees
{
    public function activeEmployees(): array
    {
        return $this->client->query($this->getFullPath() . '/employees')
            ->setModel(Employee::class)
            ->includeInactive(false)
            ->includeFuture(false)
            ->getAll()
            ->all();
    }

    public function inactiveEmployees(): array
    {
        return $this->client->query($this->getFullPath() . '/employees')
            ->setModel(Employee::class)
            ->includeInactive(true)
            ->includeFuture(false)
            ->getAll()
            ->all();
    }

    public function futureEmployees(): array
    {
        return $this->client->query($this->getFullPath() . '/employees')
            ->setModel(Employee::class)
            ->includeInactive(false)
            ->includeFuture(true)
            ->getAll()
            ->all();
    }

    public function allEmployees(): array
    {
        return $this->client->query($this->getFullPath() . '/employees')
                ->setModel(Employee::class)
                ->includeInactive(true)
                ->includeFuture(true)
                ->getAll()
                ->all();
    }

    public function getEmployee(string $key): ?Employee
    {
        foreach ($this->allEmployees() as $employee) {
            if ($employee->id == $key) {
                return $employee;
            }
        }
        return null;
    }

    public function findEmployee(array $attributes, ?string $when = 'now'): ?Employee
    {
        foreach ($this->allEmployees() as $employee) {
            if ($employee->matches($attributes)) {
                if (is_null($when)) {
                    return $employee;
                } elseif (dateTimeWithin($when, $employee->from, $employee->to)) {
                    return $employee;
                }
            }
        }
        return null;
    }

    public function findEmployees(array $attributes, string $from = null, string $to = null, bool $strict = false): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($from) && is_null($to)) {
            return $this->allEmployees();
        }

        $foundEmployees = [];

        foreach ($this->allEmployees() as $employee) {
            if ($employee->matches($attributes)) {
                if (intervalWithin($employee->from, $employee->to, $from, $to, $strict)) {
                    $foundEmployees[] = $employee;
                }
            }
        }
        return $foundEmployees;
    }

    public function hasEmployee(array $attributes, ?string $when = 'now'): bool
    {
        return (bool)$this->findEmployee($attributes, $when);
    }

    // TODO: fix PayRates, Contracts & Properties
    public function copyEmployee(Employee $source, bool $addUser = true, bool $addPayRates = true, bool $addContracts = true, bool $addProperties = true, bool $autoHandleNumberConflict = false): array|Employee
    {
        $errors = [];

        // If number already exists, get the next available, else use number from source
        $hasEmployeeWithSameNumber = $this->hasEmployee([ 'number' => $source->number ], null);

        if (!$autoHandleNumberConflict && $hasEmployeeWithSameNumber) return [];

        $number = $autoHandleNumberConflict
            ? ($hasEmployeeWithSameNumber
                ? $this->nextBadgeNumber()
                : $source->number)
            : $source->number;

        $user = $source->user();

        // Create new Employee
        if ($addUser && !is_null($user)) {

            // Create User Access
            $this->addUserAccess($user);

            // Try adding to User Group
            $ugs = $source->user()->userGroups();

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
                'number' => $number,
                'from' => $source->from,
                'to' => $source->to,
                'extra_seniority' => $source->extra_seniority,
                'country_key' => $source->country_key,
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
                'number' => $number,
                'from' => $source->from,
                'to' => $source->to,
                'extra_seniority' => $source->extra_seniority,
                'country_key' => $source->country_key,
                'nationality' => $source->nationality,
                'region_id' => $source->region_id
            ])->setPath("/customers/$this->id/employees");
        }

        // Save
        $newEmployee->save();

        // Add PayRates
        if ($addPayRates) {
            $payRates = $this->client->query($source->getFullPath() . '/pay_rates')
                ->setModel(EmployeePayRate::class)
                ->orderBy('created_at')
                ->direction('asc')
                ->getAll()
                ->all();

            foreach ($payRates as $payRate) {
                try {
                    $newEmployee->copyPayRate($payRate);
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        // Add Contracts
        if ($addContracts) {
            $contracts = $this->client->query($source->getFullPath() . '/contracts')
                ->setModel(EmployeeContract::class)
                ->orderBy('created_at')
                ->direction('asc')
                ->getAll()
                ->all();

            foreach ($contracts as $contract) {
                try {
                    $newEmployee->copyContract($contract);
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        // Add Properties
        if ($addProperties) {
            $properties = $this->client->query($source->getFullPath() . '/properties')
                ->setModel(Property::class)
                ->orderBy('created_at')
                ->direction('asc')
                ->getAll()
                ->all();

            foreach ($properties as $property) {
                $newEmployee->addProperty($property->key, $property->value);
            }
        }

//        if (!empty($errors)) {
//            return $errors;
//        }
        print_r($errors);
        return $newEmployee;
    }

    // TODO:
    public function addEmployee($param1, $param2): ?Employee
    {
        throw new Exception('Not implemented');
    }


    // TODO: also create for "lent" employees
    protected ?array $myExternalEmployees = null;
    public function externalEmployees(): array
    {
        return !is_null($this->myExternalEmployees)
            ? $this->myExternalEmployees
            : $this->myExternalEmployees = $this->client->query($this->getFullPath() . '/external_employees')->setModel(EmployeeExternalEmployee::class)->getAll()->all();
    }
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
                $userGroup->addMember($employee->user()->id, utcDateTimeString($fromBd));
            }
            return true;
        }
        catch (Exception $e)
        {
            return false;
        }
    }

    public function nextBadgeNumber(): int
    {
        return $this->client->query($this->getFullPath() . '/employees')
            ->setModel(Employee::class)
            ->includeInactive(true)
            ->includeFuture(true)
            ->orderBy('number')
            ->direction('desc')
            ->getAll()
            ->all()[0]->number +1;
    }

    public function transferTo(Customer $destination, string $fromBd): ?Employee
    {
        throw new \Exception('Not implemented');
    }
}