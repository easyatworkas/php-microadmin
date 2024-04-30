<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

use Exception;
use Ext\Traits\HasAbsences;
use Ext\Traits\HasMembers;
use Ext\Traits\HasOffTimes;
use Ext\Traits\HasProducts;
use Ext\Traits\HasTimepunches;

/**
 * @property mixed|null $id             PRIMARY KEY             OK
 * @property mixed|null $name
 * @property mixed|null $model          Member Model Type
 * @property mixed|null $type           Group Type
 * @property mixed|null $owner_id       FOREIGN KEY             OK
 * @property mixed|null $parent_id      KEY (optional)          OK
 * @property mixed|null $owner_type
 */
class CustomerGroup extends Model
{
    use HasAbsences;
    use HasOffTimes;
    use HasProducts;
    use HasMembers;
    use HasTimepunches;

    protected $path = '/customer_groups';

    /**
     * Gets Model from property $owner_id, matched against property $owner_type
     *
     * @return Model
     * @throws Exception
     * @author Torbjørn Kallstad
     */
    public function owner(): Model
    {
        return match ($this->owner_type) {
            'customer' => Customer::get($this->owner_id),
            'customer_group' => CustomerGroup::get($this->owner_id),
            default => throw new Exception('Cannot get Owner of type ' . $this->owner_type),
        };
    }

    /**
     * Gets Parent CustomerGroup Model from optional property $parent_id
     *
     * @return CustomerGroup|null
     * @author Torbjørn Kallstad
     */
    public function parent(): ?CustomerGroup
    {
        return is_null($this->parent_id) ? null : CustomerGroup::get($this->parent_id);
    }

    public function createPowerBIClient(bool $outputCredentials = false): ?OauthClient
    {
        try
        {
            $newClient = OauthClient::newInstance([
                'name' => $this->name . ' PowerBIClient',
            ]);

            $newClient->save();

            foreach ($this->members() as $member) {
                $newClient->addPermission("customers.$member->id.^.get", true);
            }

            if ($outputCredentials)
            {
                logg()->info("$this->name\t$newClient->id:$newClient->secret\n");
            }

            return $newClient;
        }
        catch (\Exception $e)
        {
            return null;
        }
    }
}
