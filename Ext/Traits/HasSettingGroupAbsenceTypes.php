<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\SettingGroupAbsenceType;

trait HasSettingGroupAbsenceTypes
{
    public function absenceTypes(): array
    {
        return $this->client->query($this->getFullPath() . '/absence_types')->setModel(SettingGroupAbsenceType::class)->getAll()->all();
    }
    public function getAbsenceType( string $search, string $property = 'id' ): ?SettingGroupAbsenceType
    {
        foreach ($this->absenceTypes() as $schedule)
        {
            if ($schedule->{$property} == $search) return $schedule;
        }
        return null;
    }
    public function hasAbsenceTypeWith( array $parameters ): bool
    {
        foreach ($this->absenceTypes() as $absenceType)
        {
            if ($absenceType->matches($parameters)) return true;
        }
        return false;
    }
    public function addAbsenceType( SettingGroupAbsenceType $absenceType, bool $checkExisting = true ): bool
    {
        if (!$checkExisting || !$this->hasAbsenceTypeWith([ 'name' => $absenceType->name, 'span' => $absenceType->span ]))
        {
            eaw()->create('/setting_groups/' . $this->id . '/absence_types/', null, array_filter([
                'name' => $absenceType->name,
                'span' => $absenceType->span,
                'from' => $absenceType->from,
                'to' => $absenceType->to,
                'paid' => $absenceType->paid,
                'code' => $absenceType->code,
                'gradable' => $absenceType->gradable,
                'color' => $absenceType->color,
                'off_time' => $absenceType->off_time,
            ], static function($var) {return $var !== null;} ));
            return true;
        }
        return false;
    }
}