<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\SeoMarkupGraphQl\Block\Head\Json\Breadcrumbs;

use Magento\Catalog\Model\Category;

class Catalog extends \MageWorx\SeoMarkup\Block\Head\Json\Breadcrumbs\Catalog
{
    /**
     * Build the path from the current registry, bypassing the helper's per-instance cache shared across list items.
     *
     * @return array
     */
    protected function getBreadcrumbs()
    {
        $crumbs   = $this->getHomeBreadcrumbs();
        $category = $this->helperCatalogData->getCategory();
        $product  = $this->helperCatalogData->getProduct();

        if ($category instanceof Category) {
            $pathIds    = array_reverse(explode(',', (string)$category->getPathInStore()));
            $categories = $category->getParentCategories();

            foreach ($pathIds as $categoryId) {
                if (empty($categories[$categoryId]) || !$categories[$categoryId]->getName()) {
                    continue;
                }

                $isLink = $product || $categoryId != $category->getId();
                $crumbs = $this->addCrumb(
                    'category' . $categoryId,
                    [
                        'label' => $categories[$categoryId]->getName(),
                        'link'  => $isLink ? $categories[$categoryId]->getUrl() : ''
                    ],
                    $crumbs
                );
            }
        }

        if ($product) {
            $crumbs = $this->addCrumb('product', ['label' => $product->getName()], $crumbs);
        }

        return $this->applyCurrentUrl($crumbs);
    }

    /**
     * The current entity has no link in the GraphQL context; take it from the model.
     *
     * @param array $crumbs
     * @return array
     */
    protected function applyCurrentUrl(array $crumbs): array
    {
        $currentUrl = (string)$this->getData('current_url');

        if (!$currentUrl || empty($crumbs)) {
            return $crumbs;
        }

        end($crumbs);
        $lastKey = key($crumbs);
        reset($crumbs);

        if ($lastKey !== null && empty($crumbs[$lastKey]['link'])) {
            $crumbs[$lastKey]['link'] = $currentUrl;
        }

        return $crumbs;
    }
}
