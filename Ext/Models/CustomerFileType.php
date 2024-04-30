<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id             PRIMARY KEY         OK
 * @property mixed|null $name
 * @property mixed|null $owner_id       FOREIGN KEY         OK
 * @property mixed|null $owner_type
 * @property bool       $mandatory
 */
class CustomerFileType extends Model
{
    protected $path = '/customers/{customer}/hr_file_types';

    protected array $validProviders = [
        // 'Modules\Signicat\SignatureProviders\SignicatSignatureProvider',
        'Modules\DigiSign\SignatureProviders\CheckboxSignatureProvider',
        'Modules\DigiSign\SignatureProviders\DocumentServiceSignatureProvider',
    ];

    /**
     * Gets Model from property $owner_id, matched against property $owner_type
     *
     * @return \Ext\Models\Customer
     * @throws \Exception
     * @author Torbjørn Kallstad
     */
    public function owner(): Model
    {
        return match ($this->owner_type) {
            'customer' => Customer::get($this->owner_id),
            default => throw new \Exception('Cannot get Owner of type ' . $this->owner_type),
        };
    }

    /**
     * Gets list of SignatureProvider Models (if any) for this FileType
     *
     * @return array
     * @throws \Exception
     * @author Torbjørn Kallstad
     */
    public function getSignatureProviders(): array
    {
        $found = [];

        foreach ($this->owner()->signables() as $signable) {
            if ($this->id == $signable['filter']['type_id']) {
                $found[] = $signable;
            }
        }
        return $found;
    }

    /**
     * Makes this FileType signable with a specified Provider
     * Previously existing Provider(s) will be deleted by default
     *
     * @param string $withProvider (see $validProviders)
     * @param bool   $replaceExisting
     *
     * @return bool
     * @throws \Exception
     * @author Torbjørn Kallstad
     */
    public function makeSignable(string $withProvider, bool $replaceExisting = true): bool
    {
        // Return false if invalid Provider is requested
        if (!in_array($withProvider, $this->validProviders)) return false;

        // Check if any SignatureProviders already exist
        $existing = $this->getSignatureProviders();

        // Remove any existing Signables?
        if ($replaceExisting) {
            foreach ($existing as $item) {
                $item->delete();
            }
        } else {
            // Return true if requested provider already exists
            foreach ($existing as $item) {
                if ($item->provider == $withProvider) {
                    return true;
                }
            }
        }

        // Create new SignatureProvider
        try {
            eaw()->create("/customers/$this->owner_id/signables", null,
                [
                    'model' => 'Modules\Hr\Models\File',
                    'provider' => $withProvider,
                    'filter' => ['type_id' => $this->id]
                ]);
            return true;
        } catch (\Exception $e) {
            // TODO: Log error
            return false;
        }
    }

    /**
     * Checks if this FileType is signable with a specified Provider
     * If no Provider is specified, it checks for any Provider
     *
     * @param string|null $withProvider (see $validProviders)
     *
     * @return bool
     * @throws \Exception
     * @author Torbjørn Kallstad
     */
    public function isSignable(?string $withProvider = null): bool
    {
        // Return false if invalid Provider is requested
        if (!is_null($withProvider) && !in_array($withProvider, $this->validProviders)) return false;

        // Check if a SignatureProvider exists
        $existing = $this->getSignatureProviders();

        // Return true if no Provider specified, and one or more Providers exist
        if (is_null($withProvider) && $existing) return true;

        // Return true if specified provider exists
        foreach ($existing as $item) {
            if ($item->provider == $withProvider) return true;
        }

        // Specified Provider was not found
        return false;
    }
}
