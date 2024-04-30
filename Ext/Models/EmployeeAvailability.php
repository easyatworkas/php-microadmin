<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * See the Availability Class for properties
 */
class EmployeeAvailability extends Availability
{
    protected $path = '/customers/{customer}/employees/{employee}/availabilities';
}
