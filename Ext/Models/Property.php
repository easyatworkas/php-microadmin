<?php // TODO: Not refactored

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Models;

/**
 * @property mixed|null $id         PRIMARY KEY
 * @property mixed|null $key
 * @property mixed|null $value
 * @property mixed|null $from
 * @property mixed|null $to
 */
class Property extends Model
{
    protected ?SettingGroup $mySettingGroup = null; // TODO: Replace with myOwner. Properties can belong to anything
    protected ?bool $isDynamic = null;

    // Models ----------------------------------------------------------------------------------------------------------
    public function settingGroup(): SettingGroup
    {
        if (!is_null($this->mySettingGroup)) return $this->mySettingGroup;

        return $this->mySettingGroup = SettingGroup::get(abs((int) filter_var($this->getPath(), FILTER_SANITIZE_NUMBER_INT)));
    }

    // Flags -----------------------------------------------------------------------------------------------------------
    function isDynamic(array $search) : bool
    {
        if (!is_null($this->isDynamic)) return $this->isDynamic;

        // Check if property is a dynamic property
        $this->isDynamic = false;
        foreach ($search as $string)
        {
            if (strstr($this->key, $string))
            {
                $this->isDynamic = true;
                break;
            }
        }
        return $this->isDynamic;
    }
}
