<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete: TODO: Add functions for parent() and child()
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                     PRIMARY KEY     OK
 * @property mixed|null $parent                 Customer        (see HasCustomerRelationships)
 * @property mixed|null $child                  Customer        (see HasCustomerRelationships)
 * @property mixed|null $type
 * @property mixed|null $from
 * @property mixed|null $to
 */
class CustomerLink extends Model
{
    protected $path = '/customers/{customer}/links';

    // TODO: Can not delete -- it only ends the relationship "now"
//    public function delete(): array
//    {
//        return eaw()->delete('/customers/' . $this->parent['id'] . '/links/' . $this->child['id']);
//    }

    public function endRelation(?string $when = 'now'): bool
    {
        try {
            eaw()->update('/customers/' . $this->parent['id'] . '/links/' . $this->child['id'], null, ['to' => $when]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
