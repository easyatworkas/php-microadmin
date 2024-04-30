<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

use Ext\Models\HyperLink;

trait HasHyperLinks
{
    public function allHyperLinks(): array
    {
        if (!$this->hasProduct('Links')) return [];

        return $this->client->query($this->getFullPath() . '/hyper_links')
            ->setModel(HyperLink::class)
            ->getAll()
            ->all();
    }

    public function ownHyperLinks(): array
    {
        return $this->findHyperLinks([ 'customer_id' => $this->id ]);
    }

    public function inheritedHyperLinks(): array
    {
        return $this->findHyperLinks([ 'customer_id' => !$this->id ]);
    }

    public function getHyperLink(string $key): ?HyperLink
    {
        foreach ($this->allHyperLinks() as $model) {
            if ($model->{$model->keyName} == $key) {
                return $model;
            }
        }
        return null;
    }

    public function findHyperLink(array $attributes): ?HyperLink
    {
        foreach ($this->allHyperLinks() as $model) {
            if ($model->matches($attributes)) {
                return $model;
            }
        }
        return null;
    }

    public function findHyperLinks(array $attributes): array
    {
        // If no params provided, return all
        if (empty($attributes)) {
            return $this->allHyperLinks();
        }

        $found = [];

        foreach ($this->allHyperLinks() as $model) {
            if ($model->matches($attributes)) {
                $found[] = $model;
            }
        }
        return $found;
    }

    public function hasHyperLink(array $attributes): bool
    {
        return (bool)$this->findHyperLink($attributes);
    }

    public function addHyperLink(string $url, string $text, ?string $description, bool $inheritable = false): ?HyperLink
    {
        if (!$this->hasProduct('Links')) return null;

        // Create new HyperLink
        $newModel = HyperLink::newInstance([
            'url' => $url,
            'text' => $text,
            'description' => $description,
            'inheritable' => $inheritable,
        ])->setPath($this->getFullPath() . '/hyper_links');

        $newModel->save();

        return $newModel;
    }

    public function copyHyperLink(HyperLink $source): ?HyperLink
    {
        return $this->addHyperLink($source->url, $source->text, $source->description, $source->inheritable);
    }
}