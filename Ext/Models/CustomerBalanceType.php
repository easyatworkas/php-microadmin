<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                         PRIMARY KEY     OK
 * @property mixed|null $object_id                  FOREIGN KEY
 * @property mixed|null $object_type
 * @property mixed|null $balance_code
 * @property mixed|null $balance_calculator
 * @property mixed|null $interval_configuration
 * @property mixed|null $contract_type_ids          array
 * @property mixed|null $name
 * @property mixed|null $description
 * @property mixed|null $factor
 * @property mixed|null $unit
 * @property mixed|null $from
 * @property mixed|null $to
 */
class CustomerBalanceType extends Model
{
    protected $path = '/customers/{customer}/balance_types';
}
