<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [ ] Complete: TODO: Dynamic Permissions
 */

namespace Ext\Models;

/**
 * @property mixed|null $node
 * @property mixed|null $value
 */
class Permission extends Model
{
    public $keyName = 'node';

    protected array $dynamicNodeList = [
        'customers.{id}.',
        '.user_groups.{id}.',
        '.reports.{id}.',
    ];

    public function save()
    {
        $this->attributes = $this->client->create($this->getPath(), [], $this->getAttributes());
        $this->syncOriginal();

        return true;
    }

    protected array $dynamicValueList = [];

    public function getDynamicNode(): ?array
    {
        return $this->hasDynamicPart($this->dynamicNodeList, $this->node) ? $this->dynamicPart($this->dynamicNodeList, $this->node) : null;
    }

    public function getDynamicValue(): ?array
    {
        return $this->hasDynamicPart($this->dynamicValueList, $this->value) ? $this->dynamicPart($this->dynamicValueList, $this->value) : null;
    }

    private function hasDynamicPart(array $dynamicList, string $text): bool
    {
        foreach ($dynamicList as $item) {
            // Replace {id} in $item with a regular expression that matches one or more digits
            $regex = str_replace('{id}', '\d+', $item);

            // Replace any dots (.) in $item with a literal dot (\.)
            $regex = str_replace('.', '\.', $regex);

            // Add anchors to the regex to ensure a complete match
            $regex = '/^.*' . $regex . '.*$/';

            // Check if $text matches the regular expression
            if (preg_match($regex, $text)) {
                return true;
            }
        }

        // None of the $dynamicNodeList entries matched $permission
        return false;
    }

    function dynamicPart(array $dynamicList, string $text): ?array
    {
        foreach ($dynamicList as $dynamicNode) {
            $pattern = str_replace('{id}', '(\d+)', $dynamicNode);
            $pattern = '/' . str_replace('.', '\.', $pattern) . '/';
            if (preg_match($pattern, $text, $matches)) {
                return array($matches[0], $this->id);
            }
        }

        return null;
    }
}
