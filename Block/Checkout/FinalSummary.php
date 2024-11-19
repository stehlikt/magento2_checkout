<?php

namespace Railsformers\Checkout\Block\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\ResourceConnection;
use Railsformers\FreeshippingBanner\Helper\Data as FreeShippingHelper;

class FinalSummary extends Template
{
    protected $_checkoutSession;
    protected $_priceCurrency;
    protected $_resourceConnection;
    protected $_freeShippingHelper;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        PriceCurrencyInterface $priceCurrency,
        ResourceConnection $resourceConnection,
        FreeShippingHelper $freeShippingHelper,
        array $data = []
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_priceCurrency = $priceCurrency;
        $this->_resourceConnection = $resourceConnection;
        $this->_freeShippingHelper = $freeShippingHelper;
        parent::__construct($context, $data);
    }

    public function getPaymentAmount()
    {
        $shipping = $this->_checkoutSession->getQuote()->getShippingAddress()->getShippingMethod();

        if($this->_checkoutSession->getQuote()->getPayment()->getMethod() == 'cashondelivery')
        {
            $words = explode("_", $shipping);
            $uniqueWords = array_unique($words);
            $shipping = implode("_", $uniqueWords);

            $connection = $this->_resourceConnection->getConnection();
            $tableName = $connection->getTableName('core_config_data');
            $select = $connection->select()->from($tableName,'value')->where('path = ?', 'carriers/'.$shipping.'/codprice');

            return $connection->fetchOne($select);
        }
        else
            return 0;
    }

    public function getShippingAmount()
    {
        return $this->_checkoutSession->getQuote()->getShippingAddress()->getBaseShippingAmount();
    }

    public function getTotalAmountWithoutTax()
    {
        return $this->_checkoutSession->getQuote()->getGrandTotal() - $this->_checkoutSession->getQuote()->getShippingAddress()->getTaxAmount();
    }

    public function getItemsTotal()
    {
        $cartItems = $this->_checkoutSession->getQuote()->getAllVisibleItems();
        $subtotalWithTax = 0;
        foreach ($cartItems as $item) {
            $subtotalWithTax += $item->getRowTotalInclTax();
        }
        return $subtotalWithTax;
    }

    public function getTotalAmountWithTax()
    {
        return $this->_checkoutSession->getQuote()->getGrandTotal();
    }

    public function formatPrice($price)
    {
        return $this->_priceCurrency->format($price, false);
    }

    public function isFreeShippingActive()
    {
        return $this->_freeShippingHelper->getPriceLimit();
    }

    public function getDiscount()
    {
        $quote = $this->_checkoutSession->getQuote();
        $discountAmount = 0;

        foreach ($quote->getAllItems() as $item) {
            $discountAmount += $item->getDiscountAmount();
        }

        return $discountAmount;
    }
}
