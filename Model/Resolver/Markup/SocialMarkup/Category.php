<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\SeoMarkupGraphQl\Model\Resolver\Markup\SocialMarkup;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\View\LayoutFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use MageWorx\SeoMarkup\Model\OpenGraphConfigProvider;
use MageWorx\SeoMarkup\Model\TwitterCardsConfigProvider;

class Category implements ResolverInterface
{
    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var OpenGraphConfigProvider
     */
    protected $openGraphConfigProvider;

    /**
     * @var TwitterCardsConfigProvider
     */
    protected $twCardsConfigProvider;

    /**
     * Category constructor.
     *
     * @param LayoutFactory $layoutFactory
     * @param CollectionFactory $categoryCollectionFactory
     * @param OpenGraphConfigProvider $openGraphConfigProvider
     * @param TwitterCardsConfigProvider $twCardsConfigProvider
     */
    public function __construct(
        LayoutFactory $layoutFactory,
        CollectionFactory $categoryCollectionFactory,
        OpenGraphConfigProvider $openGraphConfigProvider,
        TwitterCardsConfigProvider $twCardsConfigProvider
    ) {
        $this->layoutFactory             = $layoutFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->openGraphConfigProvider   = $openGraphConfigProvider;
        $this->twCardsConfigProvider     = $twCardsConfigProvider;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return Value|mixed
     * @throws LocalizedException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        if (!$this->openGraphConfigProvider->isEnabledForCategory()
            && !$this->twCardsConfigProvider->isEnabledForCategory()
        ) {
            return '';
        }

        /** @var \Magento\Catalog\Model\Category $category */
        $category = $value['model'];
        $category = $this->getCategory((int)$category->getId());
        $layout   = $this->layoutFactory->create();

        /** @var \MageWorx\SeoMarkup\Block\Head\SocialMarkup\Category $block */
        $block = $layout->createBlock(
            \MageWorx\SeoMarkup\Block\Head\SocialMarkup\Category::class,
            '',
            [
                'data' => []
            ]
        );
        $block->setEntity($category);

        return $block->toHtml();
    }

    /**
     * @param int $id
     * @return \Magento\Framework\DataObject|\Magento\Catalog\Model\Category
     * @throws LocalizedException
     */
    protected function getCategory(int $id)
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection
            ->addIdFilter([$id])
            ->addAttributeToSelect($this->getAttributesForCollection());

        return $collection->getFirstItem();
    }

    /**
     * @return array
     */
    protected function getAttributesForCollection(): array
    {
        $attributes = [
            'name',
            'meta_title',
            'description',
            'meta_description',
            'image'
        ];

        $ogCategoryTitleCode = $this->openGraphConfigProvider->getCategoryTitleCode();

        if ($ogCategoryTitleCode) {
            $attributes[] = $ogCategoryTitleCode;
        }

        $ogCategoryDescriptionCode = $this->openGraphConfigProvider->getCategoryDescriptionCode();

        if ($ogCategoryDescriptionCode) {
            $attributes[] = $ogCategoryDescriptionCode;
        }

        $twCategoryTitleCode = $this->twCardsConfigProvider->getCategoryTitleCode();

        if ($twCategoryTitleCode) {
            $attributes[] = $twCategoryTitleCode;
        }

        $twCategoryDescriptionCode = $this->twCardsConfigProvider->getCategoryDescriptionCode();

        if ($twCategoryDescriptionCode) {
            $attributes[] = $twCategoryDescriptionCode;
        }

        return $attributes;
    }
}
