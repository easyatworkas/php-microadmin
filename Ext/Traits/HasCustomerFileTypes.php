<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\CustomerFileType;
use Ext\Models\CustomerSignable;

trait HasCustomerFileTypes
{
    public function fileTypes(): array
    {
        if (!$this->hasProduct('Human Resources')) return [];

        return static::newQuery($this->getFullPath() . '/hr_file_types')
            ->setModel(CustomerFileType::class)
            ->getAll()
            ->all();
    }

    public function getFileType(string $key): ?CustomerFileType
    {
        $fileType = null;
        try {
            $fileType = static::newQuery($this->getFullPath() . "/hr_file_types/$key")
                ->setModel(CustomerFileType::class)
                ->get();
        } catch (Exception $e) {
            // TODO: Log error
        }
        return $fileType;
    }

    public function findFileType(array $parameters): ?CustomerFileType
    {
        foreach ($this->fileTypes() as $fileType) {
            if ($fileType->matches($parameters)) {
                return $fileType;
            }
        }
        return null;
    }

    public function findFileTypes(array $parameters): array
    {
        $foundFileTypes = [];

        foreach ($this->fileTypes() as $fileType) {
            if ($fileType->matches($parameters)) {
                $foundFileTypes[] = $fileType;
            }
        }
        return $foundFileTypes;
    }

    public function hasFileType(array $attributes): bool
    {
        return (bool)$this->findFileType($attributes);
    }

    // TODO: Check this against getSignable/hasProduct
    public function copyFileType(CustomerFileType $source, bool $checkExisting = true): ?CustomerFileType
    {
        if (!$checkExisting || !$this->findFileType(['name' => $source->name])) {
            // Get Signable for source FileType (if any)
            $signable = $this->hasProduct("Digital Signing") ? $source->owner()->getSignable($source->id,
                'filter.type_id') : null;

            // Create new FileType
            $newFileType = CustomerFileType::newInstance([
                'name' => $source->name,
                'mandatory' => $source->mandatory
            ])->setPath("/customers/$this->id/hr_file_types");
            $newFileType->save();

            // If Signable was found, it needs to be added to the FileType, not the Customer
            if (!is_null($signable)) {
                $newFileType->makeSignable($signable->provider, false);
            }

            return $newFileType;
        }
        return null;
    }

    public function addFileType(string $name, bool $mandatory = false, ?string $signatureProvider = null): ?CustomerFileType
    {
        // Create new FileType
        $newFileType = CustomerFileType::newInstance([
            'name' => $name,
            'mandatory' => $mandatory
        ])->setPath("/customers/$this->id/hr_file_types");
        $newFileType->save();

        usleep(60000);

        // If Signable was found, it needs to be added to the FileType
        if (!is_null($signatureProvider)) {
            $newFileType->makeSignable($signatureProvider);
        }

        return $newFileType;
    }
}