<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\EmployeeHrFile;

trait HasEmployeeHrFiles
{
    protected ?array $myHrFiles = null;

    public function hrFiles(): array
    {
        return !is_null($this->myHrFiles)
            ? $this->myHrFiles
            : $this->myHrFiles = $this->client->query($this->getFullPath() . '/hr_files')
                ->setModel(EmployeeHrFile::class)
                ->getAll()
                ->all();
    }

    public function findHrFile(array $attributes = []): ?EmployeeHrFile
    {
        foreach ($this->hrFiles() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function findHrFiles(array $attributes = []): array
    {
        // If no params provided, return all
        if (empty($attributes)) {
            return $this->hrFiles();
        }

        $found = [];

        foreach ($this->hrFiles() as $model) {
            if ($model->matches($attributes)) {
                $found[] = $model;
            }
        }

        return $found;
    }

    public function getHrFile(string $key): ?EmployeeHrFile
    {
        return $this->findHrFile([ 'id' => $key ]);
    }

    public function hasHrFile(array $attributes = []): bool
    {
        return (bool)$this->findHrFile($attributes);
    }
}