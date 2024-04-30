<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Ext\Models\CustomerSignable;

trait HasCustomerSignables
{
    public function signables(): array
    {
        if (!$this->hasProduct('Digital Signing')) return [];

        return static::newQuery($this->getFullPath() . '/signables')
            ->setModel(CustomerSignable::class)
            ->getAll()
            ->all();
    }

//    public function fileSignatures(): array
//    {
//        if (!$this->hasProduct('Digital Signing')) return [];
//
//        return $this->client->query($this->getFullPath() . '/signables')
//            ->setModel(CustomerSignable::class)
//            ->model('Modules\Hr\Models\File')
//            ->getAll()
//            ->all();
//    }

    public function getSignable( string $search, string $property = 'id' ): ?CustomerSignable
    {
        $properties = explode('.', $property);

        switch (count($properties))
        {
            case 1:
                foreach ($this->signables() as $signable)
                {
                    if ($signable->{$property} == $search)
                    {
                        return $signable;
                    }
                }
                break;
            case 2:
                foreach ($this->signables() as $signable)
                {
                    if ($signable[$properties[0]][$properties[1]] == $search)
                    {
                        return $signable;
                    }
                }
                break;
        }
        return null;
    }
}