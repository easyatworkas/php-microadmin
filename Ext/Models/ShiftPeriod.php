<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $offset
 * @property mixed|null $length
 * @property mixed|null $description
 * @property mixed|null $business_unit_id
 * @property mixed|null $qualifications
 * @property mixed|null $break
 * @property mixed|null $unproductive
 * @property mixed|null $color
 */
class ShiftPeriod extends Model
{
    protected $path = '/customers/{customer}/schedules/{schedule}/shifts/{shift}/periods';
}
