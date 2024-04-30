<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $rate
 * @property mixed|null $from
 * @property mixed|null $to
 */
class TariffRate extends Model
{
    protected $path = '/tariffs/{tariff}/rates';
}
