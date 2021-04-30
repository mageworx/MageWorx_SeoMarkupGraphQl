<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\SeoMarkupGraphQl\Model\Resolver\Markup\SocialMarkup;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use MageWorx\SeoMarkup\Helper\Product as HelperProduct;
use MageWorx\SeoMarkup\Model\OpenGraphConfigProvider;
use MageWorx\SeoMarkup\Model\TwitterCardsConfigProvider;

class Product implements ResolverInterface
{
    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var State
     */
    protected $appState;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var HelperProduct
     */
    protected $helperProduct;

    /**
     * @var OpenGraphConfigProvider
     */
    protected $openGraphConfigProvider;

    /**
     * @var TwitterCardsConfigProvider
     */
    protected $twCardsConfigProvider;

    /**
     * Product constructor.
     *
     * @param LayoutFactory $layoutFactory
     * @param State $appState
     * @param CollectionFactory $productCollectionFactory
     * @param HelperProduct $helperProduct
     * @param OpenGraphConfigProvider $openGraphConfigProvider
     * @param TwitterCardsConfigProvider $twCardsConfigProvider
     */
    public function __construct(
        LayoutFactory $layoutFactory,
        State $appState,
        CollectionFactory $productCollectionFactory,
        HelperProduct $helperProduct,
        OpenGraphConfigProvider $openGraphConfigProvider,
        TwitterCardsConfigProvider $twCardsConfigProvider
    ) {
        $this->layoutFactory            = $layoutFactory;
        $this->appState                 = $appState;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->helperProduct            = $helperProduct;
        $this->openGraphConfigProvider  = $openGraphConfigProvider;
        $this->twCardsConfigProvider    = $twCardsConfigProvider;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return Value|mixed
     * @throws LocalizedException
     * @throws \Exception
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        if (!$this->openGraphConfigProvider->isEnabledForProduct()
            && !$this->twCardsConfigProvider->isEnabledForProduct()
        ) {
            return '';
        }

        $product = $value['model'];
        $product = $this->getProduct((int)$product->getId());
        $layout  = $this->layoutFactory->create();

        /** @var \MageWorx\SeoMarkup\Block\Head\SocialMarkup\Product $block */
        $block = $layout->createBlock(
            \MageWorx\SeoMarkup\Block\Head\SocialMarkup\Product::class,
            '',
            [
                'data' => []
            ]
        );
        $block->setEntity($product);

        return $this->appState->emulateAreaCode(Area::AREA_FRONTEND, [$block, 'toHtml']);
    }

    /**
     * @param int $id
     * @return \Magento\Framework\DataObject|\Magento\Catalog\Model\Product
     */
    protected function getProduct(int $id)
    {
        $collection = $this->productCollectionFactory->create();
        $collection
            ->addIdFilter($id)
            ->addAttributeToSelect($this->getAttributesForCollection())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents();

        return $collection->getFirstItem();
    }

    /**
     * @return array
     */
    protected function getAttributesForCollection(): array
    {
        $attributes = [
            ProductAttributeInterface::CODE_NAME,
            ProductAttributeInterface::CODE_SHORT_DESCRIPTION,
            ProductAttributeInterface::CODE_STATUS,
            'visibility',
            'image'
        ];

        if ($this->helperProduct->isColorEnabled()) {
            $colorCode = $this->helperProduct->getColorCode();

            if ($colorCode) {
                $attributes[] = $colorCode;
            }
        }

        if ($this->helperProduct->isBrandEnabled()) {
            $brandCode = $this->helperProduct->getBrandCode();

            if ($brandCode) {
                $attributes[] = $brandCode;
            }
        }

        if ($this->helperProduct->isWeightEnabled()) {
            $attributes[] = ProductAttributeInterface::CODE_WEIGHT;
        }

        if ($this->helperProduct->isConditionEnabled()) {
            $conditionCode = $this->helperProduct->getConditionCode();

            if ($conditionCode) {
                $attributes[] = $conditionCode;
            }
        }

        $productIdCode = $this->helperProduct->getProductIdCode();

        if ($productIdCode) {
            $attributes[] = $productIdCode;
        }

        $descriptionCode = $this->helperProduct->getDescriptionCode();

        if ($descriptionCode) {
            $attributes[] = $descriptionCode;
        }

        $ogProductTitleCode = $this->openGraphConfigProvider->getProductTitleCode();

        if ($ogProductTitleCode) {
            $attributes[] = $ogProductTitleCode;
        }

        $ogProductDescriptionCode = $this->openGraphConfigProvider->getProductDescriptionCode();

        if ($ogProductDescriptionCode) {
            $attributes[] = $ogProductDescriptionCode;
        }

        $twProductTitleCode = $this->twCardsConfigProvider->getProductTitleCode();

        if ($twProductTitleCode) {
            $attributes[] = $twProductTitleCode;
        }

        $twProductDescriptionCode = $this->twCardsConfigProvider->getProductDescriptionCode();

        if ($twProductDescriptionCode) {
            $attributes[] = $twProductDescriptionCode;
        }

        return $attributes;
    }
}
