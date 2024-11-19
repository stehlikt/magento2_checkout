<?php

namespace Railsformers\Checkout\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Railsformers\Checkout\Helper\PaymentForShipping;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\App\ResourceConnection;
use Railsformers\Checkout\Block\Checkout\FinalSummary;

class SelectShipping extends Action{
    protected $_resultJasonFactory;
    protected $_payments;
    protected $_cart;
    protected $_checkoutSession;
    protected $_currency;
    protected $_resourceConnection;
    protected $_finalSummary;

    public function __construct(
        Context $context,
        JsonFactory $resultJasonFactory,
        PaymentForShipping $payments,
        Session $checkoutSession,
        CartRepositoryInterface $cart,
        PriceCurrencyInterface $currency,
        ResourceConnection $resourceConnection,
        FinalSummary $finalSummary)
    {
        $this->_resultJasonFactory = $resultJasonFactory;
        $this->_payments = $payments;
        $this->_checkoutSession = $checkoutSession;
        $this->_cart = $cart;
        $this->_currency = $currency;
        $this->_resourceConnection = $resourceConnection;
        $this->_finalSummary = $finalSummary;
        parent::__construct($context);
    }

    public function execute()
    {
        $shippingMethod = $this->getRequest()->getParam('shipping_method');
        $allowedPayments = $this->_payments->getPayments();

        $quoteId = $this->_checkoutSession->getQuote()->getId();
        $quote = $this->_cart->get($quoteId);

        $quote->getShippingAddress()->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod($shippingMethod.'_'.$shippingMethod);
        $quote->collectTotals();

        if($shippingMethod == 'freeshipping')
            $quote->getShippingAddress()->setFreeShipping(true);

        $shippingPrice = $quote->getShippingAddress()->getShippingInclTax();

        $quote->save();

        $price_without_tax = $this->_finalSummary->getTotalAmountWithoutTax();
        $price_with_tax = $this->_finalSummary->getTotalAmountWithTax();

        $result = $this->_resultJasonFactory->create();
        return $result->setData([
            'shipping_price' => $this->_currency->format($shippingPrice, false),
            'allowed_payments' => $allowedPayments[$shippingMethod],
            'payment_price' => str_contains($allowedPayments[$shippingMethod], 'cashondelivery') ? $this->getCodPrice($shippingMethod) : NULL,
            'price_without_tax' => $this->_finalSummary->formatPrice($price_without_tax, false),
            'price_with_tax' => $this->_finalSummary->formatPrice($price_with_tax, false)]);
    }

    public function getCodPrice($shippingCode)
    {
        if(!empty($_SESSION['cod_prices']))
        {
            return $this->_currency->format( $_SESSION['cod_prices'][$shippingCode], false);
        }
        else
        {
            $connection = $this->_resourceConnection->getConnection();
            $tableName = $connection->getTableName('core_config_data');
            $select = $connection->select()->from($tableName,'value')->where('path = ?', 'carriers/'.$shippingCode.'/codprice');

            return $this->_currency->format($connection->fetchOne($select), false);
        }
    }
}
