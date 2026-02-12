<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\SeoMarkupGraphQl\Model\Resolver\Markup;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class CmsPage implements ResolverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var PageRepositoryInterface
     */
    protected $pageRepository;

    /**
     * CmsPage constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param PageRepositoryInterface $pageRepository
     */
    public function __construct(ScopeConfigInterface $scopeConfig, PageRepositoryInterface $pageRepository)
    {
        $this->scopeConfig    = $scopeConfig;
        $this->pageRepository = $pageRepository;
    }

    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|Value|mixed
     * @throws LocalizedException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        if (!isset($value[PageInterface::PAGE_ID])) {
            throw new LocalizedException(__('"%1" value should be specified', PageInterface::PAGE_ID));
        }

        /** @var \Magento\Cms\Model\Page $cmsPage */
        $cmsPage = $this->pageRepository->getById($value[PageInterface::PAGE_ID]);

        return [
            'model'                => $cmsPage,
            PageInterface::PAGE_ID => $value[PageInterface::PAGE_ID],
            'is_home_page'         => $this->isHomePage($cmsPage)
        ];
    }

    /**
     * @param \Magento\Cms\Model\Page $page
     * @return bool
     */
    protected function isHomePage(\Magento\Cms\Model\Page $page): bool
    {
        $homePageId     = null;
        $homeIdentifier = $this->scopeConfig->getValue(
            \Magento\Cms\Helper\Page::XML_PATH_HOME_PAGE,
            ScopeInterface::SCOPE_STORE
        );

        if (strpos($homeIdentifier, '|') !== false) {
            list($homeIdentifier, $homePageId) = explode('|', $homeIdentifier);
        }

        return $homeIdentifier == $page->getIdentifier();
    }
}
