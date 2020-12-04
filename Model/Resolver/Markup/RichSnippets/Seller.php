<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\SeoMarkupGraphQl\Model\Resolver\Markup\RichSnippets;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\View\LayoutFactory;
use MageWorx\SeoMarkup\Helper\Seller as HelperSeller;

class Seller implements ResolverInterface
{
    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var HelperSeller
     */
    protected $helperSeller;

    /**
     * Seller constructor.
     *
     * @param LayoutFactory $layoutFactory
     * @param HelperSeller $helperSeller
     */
    public function __construct(LayoutFactory $layoutFactory, HelperSeller $helperSeller)
    {
        $this->layoutFactory = $layoutFactory;
        $this->helperSeller  = $helperSeller;
    }

    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return Value|mixed
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->helperSeller->isRsEnabled()) {
            return '';
        }

        $layout = $this->layoutFactory->create();
        $data   = [];

        if (!empty($value['is_home_page'])) {
            $data['is_home_page'] = $value['is_home_page'];
        }

        /** @var \MageWorx\SeoMarkup\Block\Head\Json\Seller $block */
        $block = $layout->createBlock(
            \MageWorx\SeoMarkup\Block\Head\Json\Seller::class,
            '',
            [
                'data' => $data
            ]
        );

        return $block->toHtml();
    }
}
