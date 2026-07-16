<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\SeoMarkupGraphQl\Test\Unit\Model\Resolver\Markup\RichSnippets;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Cms\Block\Page as CmsPageBlock;
use Magento\Cms\Helper\Page as CmsPageHelper;
use Magento\Cms\Model\Page as CmsPage;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Layout;
use Magento\Framework\View\LayoutFactory;
use Magento\Theme\Block\Html\Breadcrumbs as ThemeBreadcrumbs;
use MageWorx\SeoMarkup\Block\Head\Json\Breadcrumbs\Home as HomeBlock;
use MageWorx\SeoMarkup\Helper\Breadcrumbs as HelperBreadcrumbs;
use MageWorx\SeoMarkupGraphQl\Block\Head\Json\Breadcrumbs\Catalog as CatalogBlock;
use MageWorx\SeoMarkupGraphQl\Block\Head\Json\Breadcrumbs\Page as PageBlock;
use MageWorx\SeoMarkupGraphQl\Model\Resolver\Markup\RichSnippets\Breadcrumbs;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BreadcrumbsTest extends TestCase
{
    /**
     * @var LayoutFactory|MockObject
     */
    private $layoutFactory;

    /**
     * @var HelperBreadcrumbs|MockObject
     */
    private $helperBreadcrumbs;

    /**
     * @var Registry|MockObject
     */
    private $registry;

    /**
     * @var CategoryRepositoryInterface|MockObject
     */
    private $categoryRepository;

    /**
     * @var CmsPageHelper|MockObject
     */
    private $cmsPageHelper;

    /**
     * @var CategoryCollectionFactory|MockObject
     */
    private $categoryCollectionFactory;

    /**
     * @var Breadcrumbs
     */
    private $resolver;

    /**
     * @var array
     */
    private $registryStore = [];

    /**
     * @var array|null Captured (type, args) of the snippet block created for the model
     */
    private $capturedSnippet;

    protected function setUp(): void
    {
        $this->layoutFactory      = $this->createMock(LayoutFactory::class);
        $this->helperBreadcrumbs  = $this->createMock(HelperBreadcrumbs::class);
        $this->registry           = $this->createMock(Registry::class);
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->cmsPageHelper             = $this->createMock(CmsPageHelper::class);
        $this->categoryCollectionFactory = $this->createMock(CategoryCollectionFactory::class);
        $this->registryStore             = [];
        $this->capturedSnippet           = null;

        $this->resolver = new Breadcrumbs(
            $this->layoutFactory,
            $this->helperBreadcrumbs,
            $this->registry,
            $this->categoryRepository,
            $this->cmsPageHelper,
            $this->categoryCollectionFactory
        );
    }

    public function testResolveThrowsWhenModelMissing(): void
    {
        $this->helperBreadcrumbs->expects($this->never())->method('isRsEnabled');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('"model" value should be specified');

        $this->resolver->resolve($this->createMock(Field::class), null, $this->createResolveInfo(), []);
    }

    public function testResolveReturnsEmptyStringWhenRichSnippetsDisabled(): void
    {
        $this->helperBreadcrumbs->method('isRsEnabled')->willReturn(false);
        // Disabled short-circuits before any layout is built.
        $this->layoutFactory->expects($this->never())->method('create');

        $result = $this->resolver->resolve(
            $this->createMock(Field::class),
            null,
            $this->createResolveInfo(),
            ['model' => $this->createMock(DataObject::class)]
        );

        $this->assertSame('', $result);
    }

    public function testResolveReturnsEmptyStringForUnsupportedModel(): void
    {
        $this->helperBreadcrumbs->method('isRsEnabled')->willReturn(true);

        $layout = $this->createMock(Layout::class);
        // Only the placeholder theme breadcrumbs block is created; no snippet block for an unknown model.
        $layout->expects($this->once())->method('createBlock');
        $this->layoutFactory->method('create')->willReturn($layout);

        // A model that is neither Product, Category nor CMS Page must not touch the registry.
        $this->registry->expects($this->never())->method('register');

        $result = $this->resolver->resolve(
            $this->createMock(Field::class),
            null,
            $this->createResolveInfo(),
            ['model' => $this->createMock(DataObject::class)]
        );

        $this->assertSame('', $result);
    }

    public function testResolveRendersCatalogBlockForProduct(): void
    {
        $this->helperBreadcrumbs->method('isRsEnabled')->willReturn(true);
        $this->emulateRegistry();
        $this->setUpLayout('<script>PRODUCT</script>');

        $product = $this->createMock(Product::class);
        $product->method('getCategory')->willReturn(null);
        $product->method('getCategoryIds')->willReturn([]);
        $product->method('getProductUrl')->willReturn('http://x/p.html');
        $product->method('getStoreId')->willReturn(1);

        $result = $this->resolveModel(['model' => $product]);

        $this->assertSame('<script>PRODUCT</script>', $result);
        $this->assertSame(CatalogBlock::class, $this->capturedSnippet['type']);
        $this->assertSame('http://x/p.html', $this->capturedSnippet['args']['data']['current_url']);
        // Registry returned to its initial (empty) state.
        $this->assertArrayNotHasKey('current_product', $this->registryStore);
        $this->assertArrayNotHasKey('current_category', $this->registryStore);
    }

    public function testResolveSelectsDeepestActiveCategoryForProduct(): void
    {
        $this->helperBreadcrumbs->method('isRsEnabled')->willReturn(true);
        $this->emulateRegistry();
        $this->setUpLayout('<script>PRODUCT</script>');

        $product = $this->createMock(Product::class);
        $product->method('getCategory')->willReturn(null);
        $product->method('getCategoryIds')->willReturn([5, 10]);
        $product->method('getProductUrl')->willReturn('http://x/p.html');
        $product->method('getStoreId')->willReturn(1);

        $deepest = $this->createMock(Category::class);
        $deepest->method('getId')->willReturn(10);

        $collection = $this->createMock(CategoryCollection::class);
        $collection->method('setStoreId')->willReturnSelf();
        $collection->method('addIdFilter')->willReturnSelf();
        $collection->method('addAttributeToFilter')->willReturnSelf();
        $collection->method('addAttributeToSort')->willReturnSelf();
        $collection->method('setPageSize')->willReturnSelf();
        $collection->method('getFirstItem')->willReturn($deepest);
        $this->categoryCollectionFactory->method('create')->willReturn($collection);

        // The deepest category (id 10), not the first id (5), must be the one loaded.
        $this->categoryRepository->expects($this->once())
            ->method('get')
            ->with(10, 1)
            ->willReturn($this->createMock(Category::class));

        $this->assertSame('<script>PRODUCT</script>', $this->resolveModel(['model' => $product]));
    }

    public function testResolveRendersCatalogBlockForCategory(): void
    {
        $this->helperBreadcrumbs->method('isRsEnabled')->willReturn(true);
        $this->emulateRegistry();
        $this->setUpLayout('<script>CATEGORY</script>');

        $category = $this->createMock(Category::class);
        $category->method('getUrl')->willReturn('http://x/c');

        $result = $this->resolveModel(['model' => $category]);

        $this->assertSame('<script>CATEGORY</script>', $result);
        $this->assertSame(CatalogBlock::class, $this->capturedSnippet['type']);
        $this->assertSame('http://x/c', $this->capturedSnippet['args']['data']['current_url']);
    }

    public function testResolveRendersHomeBlockForCmsHomePage(): void
    {
        $this->helperBreadcrumbs->method('isRsEnabled')->willReturn(true);
        $this->emulateRegistry();
        $this->setUpLayout('<script>HOME</script>');

        $result = $this->resolveModel(['model' => $this->createMock(CmsPage::class), 'is_home_page' => true]);

        $this->assertSame('<script>HOME</script>', $result);
        $this->assertSame(HomeBlock::class, $this->capturedSnippet['type']);
        // Home has no current entity, so no current_url is passed.
        $this->assertArrayNotHasKey('current_url', $this->capturedSnippet['args']['data']);
    }

    public function testResolveRendersPageBlockForCmsInnerPage(): void
    {
        $this->helperBreadcrumbs->method('isRsEnabled')->willReturn(true);
        $this->emulateRegistry();
        $this->setUpLayout('<script>PAGE</script>');

        $page = $this->createMock(CmsPage::class);
        $page->method('getId')->willReturn(5);
        $this->cmsPageHelper->method('getPageUrl')->with(5)->willReturn('http://x/about');

        $result = $this->resolveModel(['model' => $page]);

        $this->assertSame('<script>PAGE</script>', $result);
        $this->assertSame(PageBlock::class, $this->capturedSnippet['type']);
        $this->assertSame('http://x/about', $this->capturedSnippet['args']['data']['current_url']);
    }

    public function testResolveRestoresRegistryWhenRenderingThrows(): void
    {
        $initialProduct  = $this->createMock(Product::class);
        $initialCategory = $this->createMock(Category::class);
        $this->helperBreadcrumbs->method('isRsEnabled')->willReturn(true);
        $this->emulateRegistry(['current_product' => $initialProduct, 'current_category' => $initialCategory]);
        $this->setUpLayout('', true);

        $product = $this->createMock(Product::class);
        $product->method('getCategory')->willReturn(null);
        $product->method('getCategoryIds')->willReturn([]);
        $product->method('getProductUrl')->willReturn('http://x/p.html');
        $product->method('getStoreId')->willReturn(1);

        try {
            $this->resolveModel(['model' => $product]);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        // finally must restore the caller's registry regardless of the failure.
        $this->assertSame($initialProduct, $this->registryStore['current_product']);
        $this->assertSame($initialCategory, $this->registryStore['current_category']);
    }

    /**
     * @param array $value
     * @return mixed
     */
    private function resolveModel(array $value)
    {
        return $this->resolver->resolve($this->createMock(Field::class), null, $this->createResolveInfo(), $value);
    }

    /**
     * @param array $initial
     * @return void
     */
    private function emulateRegistry(array $initial = []): void
    {
        $this->registryStore = $initial;
        $this->registry->method('registry')->willReturnCallback(
            fn(string $key) => $this->registryStore[$key] ?? null
        );
        $this->registry->method('register')->willReturnCallback(
            function (string $key, $value): void {
                $this->registryStore[$key] = $value;
            }
        );
        $this->registry->method('unregister')->willReturnCallback(
            function (string $key): void {
                unset($this->registryStore[$key]);
            }
        );
    }

    /**
     * Wire the layout so the snippet block returns $html (or throws), capturing which block type was built.
     *
     * @param string $html
     * @param bool $throw
     * @return void
     */
    private function setUpLayout(string $html, bool $throw = false): void
    {
        $snippet = $this->createMock(BlockInterface::class);
        if ($throw) {
            $snippet->method('toHtml')->willThrowException(new \RuntimeException('boom'));
        } else {
            $snippet->method('toHtml')->willReturn($html);
        }

        $layout = $this->createMock(Layout::class);
        $layout->method('createBlock')->willReturnCallback(
            function (string $type, string $name = '', array $args = []) use ($snippet) {
                if ($type === ThemeBreadcrumbs::class || $type === CmsPageBlock::class) {
                    return $this->createMock(BlockInterface::class);
                }
                $this->capturedSnippet = ['type' => $type, 'args' => $args];

                return $snippet;
            }
        );

        $this->layoutFactory->method('create')->willReturn($layout);
    }

    /**
     * @return ResolveInfo|MockObject
     */
    private function createResolveInfo(): ResolveInfo
    {
        return $this->getMockBuilder(ResolveInfo::class)->disableOriginalConstructor()->getMock();
    }
}
