<?php

namespace Tan\EnhancedEcommerce\Test\Unit\ViewModel\Checkout\Cart;

use Tan\EnhancedEcommerce\ViewModel\CartProductInformation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Catalog\Model\Category;

class CartProductInformationTest extends TestCase
{
    /**
     * @var Cart|MockObject
     */
    private $cart;

    /**
     * @var CategoryRepositoryInterface|MockObject
     */
    private $categoryRepository;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var CartProductInformation
     */
    private $cartProductInformation;

    /**
     * @var EventManager|MockObject
     */
    private $eventManager;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->cart = $this->createMock(Cart::class);
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->eventManager = $this->createMock(EventManager::class);

        $this->cartProductInformation = new CartProductInformation(
          $this->cart,
          $this->categoryRepository,
          $this->productRepository,
          $this->eventManager
        );
    }

    public function testGetItems()
    {
        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getProduct',
                    'getParentCategoryName',
                    'setParentCategoryName',
                    'setCategoriesName',
                    'getOptionByCode'
                ]
            )
            ->getMock();

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getCategoryIds', 'getAttributeText', 'getLinkToPcmPromotion'])
            ->getMock();

        $items = [$quoteItem];
        $categoryIds = [15];
        $categoryName = 'COFFEE';

        $this->cart->expects($this->once())
            ->method('getItems')
            ->willReturn($items);

        $quoteItem->expects($this->exactly(2))
            ->method('getProduct')
            ->willReturn($product);
        $product->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->productRepository->expects($this->once())
            ->method('getById')
            ->willReturn($product);

        $product->expects($this->once())
            ->method('getCategoryIds')
            ->willReturn($categoryIds);

        $category = $this->createMock(Category::class);

        $this->categoryRepository->expects($this->once())
            ->method('get')
            ->withConsecutive(array_values($categoryIds))
            ->willReturn($category);
        $category->expects($this->once())
            ->method('getName')
            ->willReturnOnConsecutiveCalls($categoryName);

        $quoteItem->expects($this->once())
            ->method('getParentCategoryName')
            ->willReturn(null);
        $quoteItem->expects($this->once())
            ->method('setParentCategoryName')
            ->with($categoryName)
            ->willReturnSelf();
        $quoteItem->expects($this->once())
            ->method('setCategoriesName')
            ->with($categoryName . ',')
            ->willReturnSelf();
        $product->expects($this->exactly(2))
            ->method('getAttributeText')
            ->withConsecutive(['partner_brand'], ['range'])
            ->willReturnOnConsecutiveCalls(['Zoegas'], ['Holder']);
        $product->expects($this->once())
            ->method('getLinkToPcmPromotion')
            ->willReturn('LINK_TO_PCM_PROMOTION');

        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with('addAditionalGaCartItemData', ['item' => $quoteItem]);

        $this->assertSame($items, $this->cartProductInformation->getItems());
    }
}
