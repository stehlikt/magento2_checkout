<?php

namespace Railsformers\Checkout\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Session as CheckoutSession;

class Delete extends Action
{
    protected $_checkoutSession;
    protected $_resultJsonFactory;

    public function __construct(Context $context, CheckoutSession $checkoutSession, ResultFactory $resultJsonFactory)
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_resultJsonFactory = $resultJsonFactory->create(ResultFactory::TYPE_JSON);
        parent::__construct($context);
    }

    public function execute()
    {
        $response = ['success' => false];
        $itemId = $this->getRequest()->getParam('id');
        if ($itemId) {
            try {
                $quote = $this->_checkoutSession->getQuote();
                $quote->removeItem($itemId);
                $quote->setTotalsCollectedFlag(false);
                $quote->save();
                $quote->collectTotals()->save();
                $this->_checkoutSession->getQuote()->collectTotals()->save();
                $this->messageManager->addSuccessMessage(__('Položka byla odstraněna z košíku.'));
                $response['success'] = true;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('An error occurred while removing the item from the cart.'));
            }
        }
        return $this->_resultJsonFactory->setData($response);
    }
}