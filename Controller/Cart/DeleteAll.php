<?php

namespace Railsformers\Checkout\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;

class DeleteAll extends Action
{
    protected $checkoutSession;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        try {
            $this->checkoutSession->getQuote()->removeAllItems();
            $this->checkoutSession->getQuote()->save();
            $this->checkoutSession->getQuote()->collectTotals()->save();
            $this->messageManager->addSuccessMessage(__('Veškerý obsah košíku byl úspěšně vymazán.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Při mazání obsahu košíku došlo k chybě.'));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('checkout');
        return $resultRedirect;
    }
}