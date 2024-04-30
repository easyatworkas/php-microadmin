<?php

/*
 * [ ] Refactor
 * [ ] Reformat
 * [ ] Complete
 */

namespace Ext\Traits;

use Exception;
use Ext\Models\Product;

trait HasProducts
{
    private array $coreProducts = [
        'Core',
        'Billing',
        'Messaging',
        'Weather'
    ];

    public function products(): array
    {
        return $this->client->query($this->getFullPath() . '/products')
            ->setModel(Product::class)
            ->getAll()
            ->all();
    }

    public function addProduct(string $name): bool
    {
        if (in_array($name, $this->coreProducts)) return false;

        if (!$this->hasProduct($name))
        {
            eaw()->create($this->getFullPath() . '/products/', null, [ 'product_name' => $name ]);
            return true;
        }
        return false;
    }

    public function removeProduct(string $name): bool
    {
        try {
            eaw()->delete($this->getFullPath() . "/products/$name");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function hasProduct(string $name): bool
    {
        foreach ($this->products() as $product) {
            if ($product->name == $name) {
                return true;
            }
        }

        return false;
    }

    public function findProducts(string $subscriberType = 'any'): array
    {
        $found = [];

        switch ($subscriberType)
        {
            case 'customer':
            case 'group':
                foreach ($this->products() as $product)
                {
                    if (($product->pivot['subscriber_type'] ?? '') == $subscriberType) {
                        $found[] = $product;
                    }
                }
                return $found;
            case 'any':
                return $this->products();
            default:
                return $found;
        }
    }

    // Activate Product | TODO: refactor
    function activateProduct(string $product, callable $cb)
    {
        while (true) {
            try {
                // Check if product is already active
                if (!$this->hasProduct($product)) {
                    logg()->notice('Activating product [' . $product . ']'.PHP_EOL);
                    eaw()->create($this->getFullPath() . '/products', null, ['product_name' => $product]);
                }
                return call_user_func($cb, $product);
            } catch (Exception $exception) {
                sleep(3);
                continue;
            }
        }
    }
}
