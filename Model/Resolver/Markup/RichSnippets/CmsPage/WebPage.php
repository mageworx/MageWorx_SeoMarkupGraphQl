<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\SeoMarkupGraphQl\Model\Resolver\Markup\RichSnippets\CmsPage;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\View\LayoutFactory;

class WebPage implements ResolverInterface
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
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $layout = $this->layoutFactory->create();
        $block  = $layout->createBlock(\MageWorx\SeoMarkup\Block\Head\Json\Page::class, '', ['data' => []]);

        return $block->toHtml();
    }
}
