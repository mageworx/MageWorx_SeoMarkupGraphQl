<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\SeoMarkupGraphQl\Test\Unit\Model\Resolver\Markup\RichSnippets;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\State;
use Magento\Framework\View\LayoutFactory;
use MageWorx\SeoMarkup\Helper\Product as HelperProduct;
use MageWorx\SeoMarkupGraphQl\Model\Resolver\Markup\RichSnippets\Product as ProductResolver;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testAttributesForCollectionIncludeSpecialPriceAttributes(): void
    {
        $helperProduct = $this->createMock(HelperProduct::class);
        $helperProduct->method('isUseSpecialPriceFunctionality')->willReturn(true);

        $resolver = new class (
            $this->createMock(LayoutFactory::class),
            $this->createMock(State::class),
            $this->createMock(CollectionFactory::class),
            $helperProduct
        ) extends ProductResolver {
            public function getAttributes(): array
            {
                return $this->getAttributesForCollection();
            }
        };

        $attributes = $resolver->getAttributes();

        self::assertContains('special_price', $attributes);
        self::assertContains('special_from_date', $attributes);
        self::assertContains('special_to_date', $attributes);
    }
}
