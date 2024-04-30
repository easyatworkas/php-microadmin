<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

use Exception;

/**
 * @property mixed|null $member_id              FOREIGN KEY             OK
 * @property mixed|null $member_type            (custom)                OK
 * @property mixed|null $group_id               FOREIGN KEY             OK
 * @property mixed|null $from
 * @property mixed|null $to
 */
class GroupMembership extends Model
{
    /**
     * Get correct Member Model based on property $member_type and $member_id
     *
     * @return Model
     * @throws Exception
     * @author Torbjørn Kallstad
     */
    public function member(): Model
    {
        return match ($this->member_type) {
            'user' => User::get($this->member_id),
            default => throw new Exception('Can not get Members of type ' . $this->member_type),
        };
    }

    /**
     * Get correct Group Model based on property $member_type and $group_id
     *
     * @return UserGroup // TODO: Should be 'Group'
     * @throws Exception
     * @author Torbjørn Kallstad
     */
    public function group(): UserGroup
    {
        return match ($this->member_type) {
            'user' => UserGroup::get($this->group_id),
            default => throw new Exception('Can not get Group for Member of Type ' . $this->member_type),
        };
    }

    /**
     * Terminate this Group Membership
     *
     * @param string $when
     *
     * @return bool
     * @throws Exception
     * @author Torbjørn Kallstad
     */
    public function terminate(string $when = 'now'): bool
    {
        return $this->group()->removeMember($this->member_id, $when);
    }
}
