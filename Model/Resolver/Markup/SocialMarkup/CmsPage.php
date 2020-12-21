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

class CmsPage implements ResolverInterface
{
    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * CmsPage constructor.
     *
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(LayoutFactory $layoutFactory)
    {
        $this->layoutFactory = $layoutFactory;
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

        if (empty($value['is_home_page'])) {
            $blockName = \MageWorx\SeoMarkup\Block\Head\SocialMarkup\Page\DefaultPage::class;
        } else {
            $blockName = \MageWorx\SeoMarkup\Block\Head\SocialMarkup\Page\Home::class;
        }

        $layout = $this->layoutFactory->create();
        $block  = $layout->createBlock($blockName, '', ['data' => []]);
        $block->setEntity($value['model']);

        return $block->toHtml();
    }
}
