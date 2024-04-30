<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $uuid               PRIMARY KEY
 * @property mixed|null $bucket_uuid        FOREIGN KEY
 * @property mixed|null $name
 * @property mixed|null $code
 * @property mixed|null $description
 */
class MlBucketVariable extends Model
{
    protected $path = '/customers/{customer}/ml_buckets/{ml_bucket}/variables';
}
