<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\Model;

trait HasParent
{
    protected ?Model $myParent = null;

    public function parent(): ?Model
    {
        if (is_null($this->parent_id)) {
            return null;
        }

        if (!is_null($this->myParent)) {
            return $this->myParent;
        }

        return $this->myParent = $this::class::get($this->parent_id);

    }
}