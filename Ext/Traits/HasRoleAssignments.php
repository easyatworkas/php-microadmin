<?php

/*
 * [x] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\Customer;
use Ext\Models\CustomerRoleAssignment;

trait HasRoleAssignments
{
    protected ?array $myAssignments = null;

    public function assignments(): array
    {
        return !is_null($this->myAssignments)
            ? $this->myAssignments
            : $this->myAssignments = $this->client->query($this->getFullPath() . '/assignments')
                ->setModel(CustomerRoleAssignment::class)
                ->includeInactive(true)
                ->getAll()
                ->all();
    }

    public function getAssignment(string $key): ?CustomerRoleAssignment
    {
        foreach ($this->assignments() as $assignment) {
            if ($assignment->id == $key) {
                return $assignment;
            }
        }
        return null;
    }

    public function findAssignment(array $attributes, string $when = 'now'): ?CustomerRoleAssignment
    {
        foreach ($this->assignments() as $assignment) {
            if ($assignment->matches($attributes)) {
                if (dateTimeWithin($when, $assignment->from, $assignment->to)) {
                    return $assignment;
                }
            }
        }
        return null;
    }

    public function findAssignments(array $attributes, string $from = null, string $to = null, bool $strict = false): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($from) && is_null($to)) {
            return $this->assignments();
        }

        $foundAssignments = [];
        $start = is_null($from) ? $this->created_at : $from;
        $end = $to;

        foreach ($this->assignments() as $assignment) {
            if ($assignment->matches($attributes)) {
                if (intervalWithin($start, $end, $assignment->from, $assignment->to, $strict)) {
                    $foundAssignments[] = $assignment;
                }
            }
        }
        return $foundAssignments;
    }

    public function hasAssignment(array $attributes, string $when = 'now'): bool
    {
        return (bool)$this->findAssignment($attributes, $when);
    }

    public function copyAssignment(CustomerRoleAssignment $source): ?CustomerRoleAssignment
    {
        // Abort if User is not active here
        if (!Customer::get($this->getIdOf('customer'))->hasUserWith(['id' => $source->user_id])) {
            throw new Exception("User not found");
        }

        try {
            $assignment = CustomerRoleAssignment::newInstance(array_filter([
                'role_id' => $this->id,
                'user_id' => $source->user_id,
                'from' => $source->from,
                'to' => $source->to,
            ], static function ($var) {
                return $var !== null;
            }
            ))->setPath("/customers/$this->customer_id/roles/$this->id/assignments");

            $assignment->save();

            return $assignment;
        } catch (Exception $e) {
            return null;
        }
    }

    public function addAssignment(int $userId, string $localFrom = 'now', string $localTo = null): ?CustomerRoleAssignment
    {
        // Abort if User is not active here
        $customer = Customer::get($this->getIdOf('customer'));
        if (!$customer->hasUserWith([ 'id' => $userId ], $localFrom)) {
            throw new Exception("User not found");
        }

        $localTimeZone = $customer->time_zone;
        $utcFrom = utcDateTimeString($localFrom, $localTimeZone);
        $utcTo = is_null($localTo) ? null : utcDateTimeString($localTo, $localTimeZone);

        try {
            $assignment = CustomerRoleAssignment::newInstance(array_filter([
                'role_id' => $this->id,
                'user_id' => $userId,
                'from' => $utcFrom,
                'to' => $utcTo,
            ], static function ($var) {
                return $var !== null;
            }
            ))->setPath("/customers/$this->customer_id/roles/$this->id/assignments");

            $assignment->save();

            return $assignment;
        } catch (Exception $e) {
            return null;
        }
    }
}