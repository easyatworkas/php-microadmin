<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $uuid               PRIMARY KEY
 * @property mixed|null $name
 * @property mixed|null $description
 * @property mixed|null $tags               array
 */
class CustomerMlBucket extends Model
{
    public $keyName = 'uuid';
    protected $path = '/customers/{customer}/ml_buckets';

    public function variables(): array
    {
        return $this->client->query($this->getFullPath() . '/variables')
            ->setModel(MlBucketVariable::class)
            ->getAll()
            ->all();
    }
}
