<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $name
 * @property mixed|null $value
 */
class TariffRule extends Model
{
    protected $path = '/tariffs/{tariff}/rules';
}
