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
class CustomerGroupAbsence extends Absence
{
    protected $path = '/customer_groups/{customer_group}/absences';
}