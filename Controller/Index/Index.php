<?php

namespace Railsformers\Checkout\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Model\CurrencyFactory;

class Index extends Action{

    protected $resultPageFactory;
    protected $_checkoutSession;
    protected $_cart;
    private $_scopeConfig;
    private $_currency;
    protected $cookieManager;

    public function __construct(Context $context,
                                PageFactory $resultPageFactory,
                                Session $checkoutSession,
                                CartRepositoryInterface $cart,
                                ScopeConfigInterface $scopeConfig,
                                \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
                                CurrencyFactory $currency)
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_cart = $cart;
        $this->_scopeConfig = $scopeConfig;
        $this->_currency = $currency;
        $this->cookieManager = $cookieManager;
        parent::__construct($context);
    }

    public function execute()
    {

        $defaultCurrencyCode = $this->_scopeConfig->getValue('currency/options/default', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $defaultCurrency = $this->_currency->create()->load($defaultCurrencyCode);

        //session_start();
        $_SESSION['CURRENCY_SYMBOL'] = $defaultCurrency->getCurrencySymbol();

        $quote = $this->_checkoutSession->getQuote();

        if ($quote->getItemsCount() == 0 || (!$quote && !$quote->getId())) {
            // $resultRedirect = $this->resultRedirectFactory->create();
            // $resultRedirect->setPath('checkout/empty');
            // return $resultRedirect;
        }
        else
        {
            $quoteId = $quote->getId();
            $quote = $this->_cart->get($quoteId);
        }


        $quote->getShippingAddress()->setCountryId($this->_scopeConfig->getValue('general/country/default', ScopeInterface::SCOPE_WEBSITES));
        $quote->getShippingAddress()->setCollectShippingRates(true)
            ->collectShippingRates();
        $quote->collectTotals()->save();

        $quote->save();

        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }
}
