<?php

namespace Tan\EnhancedEcommerce\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;

class CartProductInformation implements ArgumentInterface
{
    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var EventManager
     */
    private $eventManager;

    public function __construct(
        Cart $cart,
        CategoryRepositoryInterface $categoryRepository,
        ProductRepositoryInterface $productRepository,
        EventManager $eventManager
    ) {
        $this->cart = $cart;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->eventManager = $eventManager;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        $items = $this->cart->getItems();
        foreach ($items as $item) {
            $product = $this->productRepository->getById($item->getProduct()->getId());
            $this->setCategoriesName($item);
            $item->setBrand($product->getAttributeText('partner_brand'));
            $item->setRange($product->getAttributeText('range'));
            $item->setLinkToPcmPromotion($product->getLinkToPcmPromotion());
            $this->eventManager->dispatch('addAditionalGaCartItemData', ['item' => $item]);
        }
        return $items;
    }

    private function setCategoriesName($item)
    {
        $categories = '';
        foreach($item->getProduct()->getCategoryIds() as $categoryId) {
            $category = $this->categoryRepository->get($categoryId);
            $categoryName = $category->getName();
            if (!$item->getParentCategoryName()) {
                $item->setParentCategoryName($categoryName);
            }
            $categories .= $categoryName . ',';
        }
        $item->setCategoriesName($categories);
        return $this;
    }
}
