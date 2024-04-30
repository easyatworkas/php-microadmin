<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id                 PRIMARY KEY             OK
 * @property mixed|null $name
 * @property mixed|null $value
 * @property mixed|null $resolve_value
 * @property mixed|null $object_id          FOREIGN KEY
 * @property mixed|null $object_type
 */
class FormField extends Model
{
    protected $path = '/customers/{customer}/default_hr_files/{default_hr_file}/form_fields';
}
