<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * See the Timepunch Class for properties
 */
class EmployeeTimepunch extends Timepunch
{
    protected $path = '/customers/{customer}/employees/{employee}/timepunches';
}
