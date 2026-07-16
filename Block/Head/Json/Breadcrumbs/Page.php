<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\SeoMarkupGraphQl\Block\Head\Json\Breadcrumbs;

class Page extends \MageWorx\SeoMarkup\Block\Head\Json\Breadcrumbs\Page
{
    /**
     * @return array
     */
    protected function getBreadcrumbs()
    {
        $crumbs     = parent::getBreadcrumbs();
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
