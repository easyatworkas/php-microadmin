<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

use Exception;

/**
 * @property mixed|null $id                 PRIMARY KEY             OK
 * @property mixed|null $owner_id           FOREIGN KEY             OK
 * @property mixed|null $owner_type
 * @property mixed|null $name
 * @property mixed|null $user_id            FOREIGN KEY (optional)  OK
 * @property mixed|null $mime
 * @property mixed|null $size
 */
class CustomerDefaultHrFileAttachment extends Model
{
    protected $path = '/customers/{customer}/default_hr_files/{default_hr_file}/attachments';

    /**
     * Gets Customer Model from property $customer_id
     *
     * @return \Ext\Models\Model
     * @throws \Exception
     * @author Torbjørn Kallstad
     */
    public function owner(): Model
    {
        return match ($this->owner_type) {
            'default_hr_document' => Customer::get($this->getIdOf('customer'))->getDefaultHrFile($this->owner_id),
            default => throw new Exception('Cannot get Owner of type ' . $this->owner_type),
        };
    }

    /**
     * Gets User Model from optional property $user_id
     *
     * @return \Ext\Models\User
     * @author Torbjørn Kallstad
     */
    public function user(): ?User
    {
        return is_null($this->user_id) ? null : User::get($this->user_id);
    }
}
