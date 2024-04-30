<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id             PRIMARY KEY         OK
 * @property mixed|null $name
 * @property mixed|null $value
 * @property mixed|null $resolve_value
 * @property mixed|null $object_id      FOREIGN KEY         OK
 * @property mixed|null $object_type
 */
class EmployeeHrFileFormField extends Model
{
    protected $path = '/customers/{customer}/employees/{employee}/hr_files/{hr_file}/form_fields';

    /**
     * Gets EmployeeHrFile from property $object_id, matched against property $object_type
     *
     * @return \Ext\Models\EmployeeHrFile
     * @throws \Exception
     * @author Torbjørn Kallstad
     */
    public function hrFile(): EmployeeHrFile
    {
        return match ($this->object_type) {
            'hr_file' => Customer::get($this->object_id),
            default => throw new \Exception('Cannot get Object of type ' . $this->object_type),
        };
    }
}
