<?php

namespace Railsformers\Checkout\Block\Cart;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Helper\Image as ProductImageHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Items extends Template
{
    protected $_cart;
    protected $_priceCurrency;
    protected $_productImageHelper;
    protected $productRepository;

    public function __construct(
        Template\Context $context,
        \Magento\Checkout\Model\Cart $cart,
        PriceCurrencyInterface $priceCurrency,
        ProductImageHelper $productImageHelper,
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->_cart = $cart;
        $this->_priceCurrency = $priceCurrency;
        $this->_productImageHelper = $productImageHelper;
        $this->productRepository = $productRepository;
        parent::__construct($context, $data);
    }

    public function getItems()
    {
        $items = $this->_cart->getQuote()->getAllVisibleItems();
        foreach ($items as $item) {
            $product = $this->productRepository->getById($item->getProductId());
            $item['thumb'] = $this->getImageUrl($product);
        }
        return $items;
    }

    public function formatPrice($price)
    {
        return $this->_priceCurrency->format($price, false);
    }

    public function getImageUrl($product)
    {
        $image = $product->getData('thumbnail');

        return $image
            ? $this->_productImageHelper
                ->init($product, 'small_image')
                ->resize(50,50)
                ->setImageFile($image)
                ->getUrl()
            : $this->_productImageHelper->getDefaultPlaceholderUrl('thumbnail');
    }
}