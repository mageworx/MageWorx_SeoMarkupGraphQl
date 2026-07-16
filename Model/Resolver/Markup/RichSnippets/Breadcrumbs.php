<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\SeoMarkupGraphQl\Model\Resolver\Markup\RichSnippets;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Cms\Helper\Page as CmsPageHelper;
use Magento\Cms\Model\Page;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use MageWorx\SeoMarkup\Helper\Breadcrumbs as HelperBreadcrumbs;

class Breadcrumbs implements ResolverInterface
{
    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var HelperBreadcrumbs
     */
    protected $helperBreadcrumbs;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var CmsPageHelper
     */
    protected $cmsPageHelper;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @param LayoutFactory $layoutFactory
     * @param HelperBreadcrumbs $helperBreadcrumbs
     * @param Registry $registry
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CmsPageHelper $cmsPageHelper
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        LayoutFactory $layoutFactory,
        HelperBreadcrumbs $helperBreadcrumbs,
        Registry $registry,
        CategoryRepositoryInterface $categoryRepository,
        CmsPageHelper $cmsPageHelper,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->layoutFactory             = $layoutFactory;
        $this->helperBreadcrumbs         = $helperBreadcrumbs;
        $this->registry                  = $registry;
        $this->categoryRepository        = $categoryRepository;
        $this->cmsPageHelper             = $cmsPageHelper;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return Value|mixed
     * @throws LocalizedException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        if (!$this->helperBreadcrumbs->isRsEnabled()) {
            return '';
        }

        $layout = $this->layoutFactory->create();
        $layout->createBlock(\Magento\Theme\Block\Html\Breadcrumbs::class, 'breadcrumbs');

        $model           = $value['model'];
        $blockName       = '';
        $blockData       = ['data' => []];
        $currentCategory = null;
        $currentProduct  = null;

        if ($model instanceof Product) {
            $currentProduct  = $model;
            $currentCategory = $this->getProductCategory($model);
            $blockName       = \MageWorx\SeoMarkupGraphQl\Block\Head\Json\Breadcrumbs\Catalog::class;
            $blockData['data']['current_url'] = (string)$model->getProductUrl();
        } elseif ($model instanceof Category) {
            $currentCategory = $model;
            $blockName       = \MageWorx\SeoMarkupGraphQl\Block\Head\Json\Breadcrumbs\Catalog::class;
            $blockData['data']['current_url'] = (string)$model->getUrl();
        } elseif ($model instanceof Page) {
            if (!empty($value['is_home_page'])) {
                $blockName = \MageWorx\SeoMarkup\Block\Head\Json\Breadcrumbs\Home::class;
            } else {
                $layout->createBlock(
                    \Magento\Cms\Block\Page::class,
                    'cms_page',
                    ['data' => ['page' => $model]]
                );
                $blockName = \MageWorx\SeoMarkupGraphQl\Block\Head\Json\Breadcrumbs\Page::class;
                $blockData['data']['current_url'] = (string)$this->cmsPageHelper->getPageUrl((int)$model->getId());
            }
        } else {
            return '';
        }

        $initialCategory = $this->registry->registry('current_category');
        $initialProduct  = $this->registry->registry('current_product');

        $this->replaceRegistryValue('current_category', $currentCategory);
        $this->replaceRegistryValue('current_product', $currentProduct);

        try {
            $block = $layout->createBlock($blockName, '', $blockData);

            return $block->toHtml();
        } finally {
            $this->replaceRegistryValue('current_product', $initialProduct);
            $this->replaceRegistryValue('current_category', $initialCategory);
        }
    }

    /**
     * @param Product $product
     * @return Category|null
     */
    protected function getProductCategory(Product $product): ?Category
    {
        $category = $product->getCategory();

        if ($category instanceof Category && $category->getId()) {
            return $category;
        }

        $categoryIds = $product->getCategoryIds();

        if (empty($categoryIds)) {
            return null;
        }

        $storeId    = (int)$product->getStoreId() ?: null;
        $categoryId = $this->resolveDeepestCategoryId($categoryIds, $storeId);

        if ($categoryId === null) {
            return null;
        }

        try {
            return $this->categoryRepository->get($categoryId, $storeId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Pick the deepest active category so the path matches what a shopper would see, deterministically.
     *
     * @param array $categoryIds
     * @param int|null $storeId
     * @return int|null
     */
    protected function resolveDeepestCategoryId(array $categoryIds, ?int $storeId): ?int
    {
        $collection = $this->categoryCollectionFactory->create();

        if ($storeId !== null) {
            $collection->setStoreId($storeId);
        }

        $collection->addIdFilter($categoryIds)
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToSort('level', 'DESC')
            ->addAttributeToSort('entity_id', 'ASC')
            ->setPageSize(1);

        $categoryId = (int)$collection->getFirstItem()->getId();

        return $categoryId ?: null;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function replaceRegistryValue(string $key, $value): void
    {
        if ($this->registry->registry($key) !== null) {
            $this->registry->unregister($key);
        }

        if ($value !== null) {
            $this->registry->register($key, $value);
        }
    }
}
