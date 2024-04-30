<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\Customer;
use Ext\Models\CustomerDefaultHrFile;
use Ext\Models\CustomerFileType;

trait HasCustomerDefaultHrFiles
{
    public function defaultHrFiles(): array
    {
        if (!$this->hasProduct('Onboarding')) return [];

        return static::newQuery($this->getFullPath() . '/default_hr_files')
            ->setModel(CustomerDefaultHrFile::class)
            ->getAll()
            ->all();
    }

    public function findDefaultHrFile(array $attributes = []): ?CustomerDefaultHrFile
    {
        foreach ($this->defaultHrFiles() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function findDefaultHrFiles(array $attributes = []): array
    {
        // If no params provided, return all
        if (empty($attributes)) {
            return $this->defaultHrFiles();
        }

        $found = [];

        foreach ($this->defaultHrFiles() as $model) {
            if ($model->matches($attributes)) {
                $found[] = $model;
            }
        }

        return $found;
    }

    public function getDefaultHrFile(string $key): ?CustomerDefaultHrFile
    {
        $defaultHrFile = null;
        try {
            $defaultHrFile = static::newQuery($this->getFullPath() . "/default_hr_files/$key")
                ->setModel(CustomerDefaultHrFile::class)
                ->get();
        } catch (\Exception $e) {
            // TODO: Log error
        }
        return $defaultHrFile;
    }

    public function hasDefaultHrFile(array $attributes = []): bool
    {
        return (bool)$this->findDefaultHrFile($attributes);
    }

    public function addDefaultHrFile(string $pathToFile, string $name, string $description, CustomerFileType $fileType, array $formFields = [] ): ?CustomerDefaultHrFile
    {
        try {
            $defaultFileResult = eaw()->create("/customers/$this->id/default_hr_files", null, [
                'name' => $name,
                'description' => $description,
                'file_type_id' => $fileType->id
            ], [
                'attachment' => fopen($pathToFile, 'r')
            ]);
            $defaultFile = static::newQuery("/customers/$this->id/default_hr_files/" . $defaultFileResult['id'])->setModel(CustomerDefaultHrFile::class)->get();

            usleep(60000);

            if (!empty($formFields))
            {
                // TODO: Use function $defaultFile->addFormFields($formFields)
                eaw()->update("/customers/$this->id/default_hr_files/$defaultFile->id/form_fields", null, [ 'form_fields' => $formFields ]);
            }

            return $defaultFile;
        } catch (\Exception $e) {
//            echo "Code: ".$e->getCode()."\t".$e->getMessage().PHP_EOL;
            return null;
        }
    }

    public function uploadDefaultHrFile( string $pathToFile, string $name, string $description, CustomerFileType $fileType, string $pathToCsv = null ): bool
    {
        try {
            $defaultFileResult = eaw()->create("/customers/$this->id/default_hr_files", null, [
                'name' => $name,
                'description' => $description,
                'file_type_id' => $fileType->id
            ], [
                'attachment' => fopen($pathToFile, 'r')
            ]);
            $defaultFile = static::newQuery("/customers/$this->id/default_hr_files/" . $defaultFileResult['id'])->setModel(CustomerDefaultHrFile::class)->get();

            if (!is_null($pathToCsv))
            {
                // Update DefaultHrFile with FormFields
                eaw()->update("/customers/$this->id/default_hr_files/$defaultFile->id/form_fields", null, [ 'form_fields' => $this->formFieldsFromCsv($pathToCsv) ]);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function copyDefaultHrFile(CustomerDefaultHrFile $source): ?CustomerDefaultHrFile
    {
        $path = $source->download();

        $fileType = $this->findFileType([ 'name' => Customer::get($source->customer_id)->getFileType($source->type_id)->name ]);

        // No valid FileType found
        if (is_null($fileType)) return null;

        $formFields = $source->formFields();

        return $this->addDefaultHrFile($path, $source->name, $source->description, $fileType, $formFields);
    }

    public function formFieldsFromCsv(?string $pathToCsv): array
    {
        $formFields = [];

        if (!is_null($pathToCsv))
        {
            $csv = fopen($pathToCsv, 'r');

            while (false !== $row = fgetcsv($csv))
            {
                $formFields[] = [
                    'name' => $row[0],
                    'value' => empty($row[1]) ? '.' : $row[1],
                    'resolve_value' => $row[2],
                ];
            }

            fclose($csv);
        }
        return $formFields;
    }
}