<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                 PRIMARY KEY     OK
 * @property mixed|null $customer_id        FOREIGN KEY     OK
 * @property mixed|null $type_id            FOREIGN KEY     OK
 * @property mixed|null $name
 * @property mixed|null $mime
 * @property mixed|null $description
 * @property mixed|null $attachments        WITH            OK
 */
class CustomerDefaultHrFile extends Model
{
    protected $path = '/customers/{customer}/default_hr_files';

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
     * Gets CustomerFileType Model from property $type_id
     *
     * @return \Ext\Models\CustomerFileType
     * @author Torbjørn Kallstad
     */
    public function fileType(): CustomerFileType
    {
        return $this->owner()->getFileType($this->type_id);
    }

    /**
     * Gets List of CustomerDefaultHrFileAttachment Models for this DefaultHrFile
     *
     * @return array
     * @author Torbjørn Kallstad
     */
    public function attachments(): array
    {
        return $this->client->query($this->getFullPath() . '/attachments')
                ->setModel(CustomerDefaultHrFileAttachment::class)
                ->getAll()
                ->all();
    }

    /**
     * Gets List of FormField Models for this DefaultHrFile
     *
     * @return array
     * @author Torbjørn Kallstad
     */
    public function formFields(): array
    {
        return $this->client->query($this->getFullPath() . '/form_fields')
            ->setModel(FormField::class)
            ->getAll()
            ->all();
    }

    /**
     * Download CustomerDefaultHrFile and return the path to the downloaded file
     *
     * @return string   Path to the downloaded file
     * @author Torbjørn Kallstad
     */
    public function download(): string
    {
        return eaw()->download('GET', $this->getFullPath() . '/download');
    }
}
