<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * See the Absence Class for properties
 */
class EmployeeAbsence extends Absence
{
    protected $path = '/customers/{customer}/employees/{employee}/absences';
}
