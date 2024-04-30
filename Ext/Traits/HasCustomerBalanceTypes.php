<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\CustomerBalanceType;

trait HasCustomerBalanceTypes
{
    public function balanceTypes(): array
    {
        // if (!$this->hasProduct('Balances')) return []; // TODO: Uncomment when Balances is its own product

        $balanceTypes = eaw()->read($this->getFullPath() . '/balance_types')['data'] ?? [];
        $result = [];

        foreach ($balanceTypes as $key => $balanceType) {
            $result[] = new CustomerBalanceType($this->client, $balanceType);
        }

        return $result;
    }
    public function getBalanceType(string $key): ?CustomerBalanceType
    {
        foreach ($this->balanceTypes() as $balanceType) {
            if ($balanceType->id == $key) {
                return $balanceType;
            }
        }
        return null;
    }
}