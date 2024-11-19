<?php

namespace Railsformers\Checkout\Block\Cart;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Railsformers\FreeshippingBanner\Helper\Data as FreeShippingHelper;

class Summary extends Template
{
    protected $_cart;
    protected $_priceCurrency;
    protected $_freeShippingHelper;

    public function __construct(
        Template\Context $context,
        \Magento\Checkout\Model\Cart $cart,
        array $data,
        PriceCurrencyInterface $priceCurrency,
        FreeShippingHelper $freeShippingHelper
    ) {
        $this->_cart = $cart;
        $this->_priceCurrency = $priceCurrency;
        $this->_freeShippingHelper = $freeShippingHelper;
        parent::__construct($context, $data);
    }

    public function getSubtotal()
    {
        $cartItems = $this->_cart->getQuote()->getAllVisibleItems();
        $subtotalWithTax = 0;
        foreach ($cartItems as $item) {
            $subtotalWithTax += $item->getRowTotalInclTax();
        }
        return $subtotalWithTax;
    }

    public function getTax()
    {
        $cartItems = $this->_cart->getQuote()->getAllVisibleItems();
        $taxAmount = 0;
        foreach ($cartItems as $item) {
            $taxAmount += $item->getTaxAmount();
        }

        return  $taxAmount;
    }

    public function getDiscount()
    {
        $cartItems = $this->_cart->getQuote()->getAllVisibleItems();
        $itemDiscountAmount = 0;
        foreach ($cartItems as $item) {
            $itemDiscountAmount += $item->getDiscountAmount();
        }
        return $itemDiscountAmount;
    }

    public function getTotal()
    {
        return $this->getSubtotal() - $this->getDiscount();
    }

    public function formatPrice($price)
    {
        return $this->_priceCurrency->format($price, false);
    }

    public function isFreeShippingActive()
    {
        return $this->_freeShippingHelper->getPriceLimit();
    }
}
