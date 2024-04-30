<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                                 PRIMARY KEY         OK
 * @property mixed|null $customer_id                        FOREIGN KEY         OK
 * @property mixed|null $parent_id                          KEY (optional)      OK
 * @property mixed|null $code
 * @property mixed|null $name
 * @property mixed|null $type
 * @property mixed|null $manable
 * @property mixed|null $reverse_inherit_qualifications
 * @property mixed|null $inherit_qualifications
 * @property mixed|null $color
 * @property mixed|null $has_open_close_hours
 * @property mixed|null $open_close
 * @property mixed|null $breadcrumb
 * @property mixed|null $top_parent                         KEY (optional)      OK
 * @property mixed|null $default
 * @property mixed|null $qualifications                     WITH (array)        OK
 */
class CustomerBusinessUnit extends Model
{
    protected $path = '/customers/{customer}/business_units';

    /**
     * Gets Customer Model from property $customer_id
     *
     * @return \Ext\Models\Customer
     * @author Torbjørn Kallstad
     */
    public function owner(): Customer
    {
        return Customer::get($this->customer_id);
    }

    /**
     * Gets Parent CustomerBusinessUnit Model from optional property $parent_id
     *
     * @return \Ext\Models\CustomerBusinessUnit|null
     * @author Torbjørn Kallstad
     */
    public function parent(): ?CustomerBusinessUnit
    {
        return is_null($this->parent_id) ? null : CustomerBusinessUnit::get($this->parent_id);
    }

    /**
     * Gets Top Parent CustomerBusinessUnit Model from optional property $top_parent
     *
     * @return \Ext\Models\CustomerBusinessUnit|null
     * @author Torbjørn Kallstad
     */
    public function topParent(): ?CustomerBusinessUnit
    {
        return is_null($this->top_parent) ? null : CustomerBusinessUnit::get($this->top_parent);
    }

    /**
     * Gets list of all CustomerQualification Models
     *
     * @return array
     * @author Torbjørn Kallstad
     */
    public function qualifications(): array
    {
        if (!is_array($this->getAttribute('qualifications'))) {
            $withCategories = static::newQuery($this->getPath())
                ->with([ 'qualifications' ])
                ->get($this->getKey());

            $this->attributes[ 'categories' ] = $withCategories->getAttribute('qualifications') ?? [];
        }

        return array_map(function (array $attributes) {
            return CustomerQualification::newInstance($attributes);
        }, $this->getAttribute('qualifications'));
    }
}
