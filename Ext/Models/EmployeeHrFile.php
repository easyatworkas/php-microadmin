<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

use Ext\Traits\HasProperties;

/**
 * @property mixed|null $id                     PRIMARY KEY     OK
 * @property mixed|null $employee_id            FOREIGN KEY     OK
 * @property mixed|null $type_id                FOREIGN KEY     OK
 * @property mixed|null $name
 * @property mixed|null $notify
 * @property mixed|null $expires_at
 * @property mixed|null $responsible_id         FOREIGN KEY     OK
 * @property mixed|null $has_form_fields
 */
class EmployeeHrFile extends Model
{
    use HasProperties;
    protected bool $hasPropertyController = false;
    protected $path = '/customers/{customer}/employees/{employee}/hr_files';

    /**
     * Gets Employee Model from property $employee_id
     *
     * @return \Ext\Models\Employee
     * @author Torbjørn Kallstad
     */
    public function employee(): Employee
    {
        return Customer::get($this->getIdOf('customer'))->getEmployee($this->employee_id);
    }

    /**
     * Gets CustomerFileType Model from property $type_id
     *
     * @return \Ext\Models\CustomerFileType
     * @author Torbjørn Kallstad
     */
    public function fileType(): CustomerFileType
    {
        return Customer::get($this->getIdOf('customer'))->getFileType($this->type_id);
    }

    /**
     * Gets User Model responsible for uploading this HR File from property $responsible_id
     *
     * @return \Ext\Models\User
     * @author Torbjørn Kallstad
     */
    public function user(): User
    {
        return User::get($this->getAttribute('responsible_id'));
    }

    /**
     * Gets CustomerDefaultHrFile Model associated with this HR File (if applicable)
     *
     * @return \Ext\Models\CustomerDefaultHrFile|null
     * @author Torbjørn Kallstad
     */
    public function defaultFile(): ?CustomerDefaultHrFile
    {
        $property = $this->getProperty('default_hr_document');
        return is_null($property) ? null : Customer::get($this->getIdOf('customer'))->getDefaultHrFile($property->value);
    }

    /**
     * Gets a List of FormField Models associated with this HR File
     *
     * @return array
     * @author Torbjørn Kallstad
     */
    public function formFields(): array
    {
        return $this->client->query($this->getFullPath() . '/form_fields')
            ->setModel(EmployeeHrFileFormField::class)
            ->getAll()
            ->all();
    }

    /**
     * Gets a List of Signature Models associated with this HR File
     *
     * @return array
     * @author Torbjørn Kallstad
     */
    public function signatures(): array
    {
        return $this->client->query($this->getFullPath() . '/signatures')
            ->setModel(EmployeeHrFileSignature::class)
            ->getAll()
            ->all();
    }

    /**
     * Checks if this HR File is signed
     *
     * @param string|null $withProvider
     *
     * @return bool
     * @author Torbjørn Kallstad
     */
    public function isSigned(?string $withProvider = 'any'): bool
    {
        $provider = match ($withProvider) {
            'any', null => 'any',
            'signicat', 'Modules\Signicat\SignatureProviders\SignicatSignatureProvider' => 'Modules\Signicat\SignatureProviders\SignicatSignatureProvider',
            'checkbox', 'Modules\DigiSign\SignatureProviders\CheckboxSignatureProvider' => 'Modules\DigiSign\SignatureProviders\CheckboxSignatureProvider',
            default => null
        };

        // Return false if invalid provider was provider
        if (is_null($provider)) return false;

        // Get all Signables
        $signatures = $this->signatures();

        // Return false if no signatures was found
        if (empty($signatures)) return false;

        // Return true if $provider is 'any' and signature(s) was found
        if ($provider == 'any') return true;

        // Match against requested provider
        foreach ($this->signatures() as $signature) {
            if ($signature->provider == $provider) return true;
        }

        // No matching signature found
        return false;
    }
}
