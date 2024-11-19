<?php

namespace Railsformers\Checkout\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Railsformers\Checkout\Helper\PaymentForShipping;
use Magento\Checkout\Model\Session;
use Railsformers\Checkout\Block\Checkout\FinalSummary;

class SelectPayment extends Action
{
    protected $_resultJsonFactory;
    protected $_payments;
    protected $_checkoutSession;
    protected $_finalSummary;

    public function __construct(Context $context, JsonFactory $resultJsonFactory, PaymentForShipping $payments, Session $checkoutSession,FinalSummary $finalSummary)
    {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_payments = $payments;
        $this->_checkoutSession = $checkoutSession;
        $this->_finalSummary = $finalSummary;
        parent::__construct($context);
    }

    public function execute()
    {
        $paymentMethod = $this->getRequest()->getParam('payment_method');

        $this->_checkoutSession->getQuote()->getPayment()->setMethod($paymentMethod)->save();

        $this->_checkoutSession->getQuote()->getShippingAddress()->setCollectShippingRates(true)
            ->collectShippingRates();
        $this->_checkoutSession->getQuote()->collectTotals();

        $result = $this->_resultJsonFactory->create();

        $price_without_tax = $this->_finalSummary->getTotalAmountWithoutTax();
        $price_with_tax = $this->_finalSummary->getTotalAmountWithTax();

        return $result->setData([
            'status' => 'success',
            'price_without_tax' => $this->_finalSummary->formatPrice($price_without_tax, false),
            'price_with_tax' => $this->_finalSummary->formatPrice($price_with_tax, false)
        ]);
    }
}
