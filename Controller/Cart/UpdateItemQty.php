<?php

namespace Railsformers\Checkout\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Controller\ResultFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class UpdateItemQty extends Action
{
    protected $_cart;
    protected $_resultJsonFactory;
    protected $_resultPageFactory;

    public function __construct(
        Context $context,
        Cart $cart,
        ResultFactory $resultFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->_cart = $cart;
        $this->_resultJsonFactory = $resultFactory->create(ResultFactory::TYPE_JSON);
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $postData = $this->getRequest()->getPostValue();
        $response = ['success' => false];

        if (!empty($postData['qty'])) {
            foreach ($postData['qty'] as $itemId => $qty) {
                try {
                    $item = $this->_cart->getQuote()->getItemById($itemId);
                    if ($item->getProduct()->getTypeId() == Configurable::TYPE_CODE) {
                        $productOptions = $item->getProduct()->getTypeInstance()->getOrderOptions($item->getProduct());
                        $superAttribute = [];
                        foreach ($productOptions['info_buyRequest']['super_attribute'] as $attributeId => $attributeValue) {
                            $superAttribute[$attributeId] = $attributeValue;
                        }
                        $params = [
                            'product' => $item->getProduct()->getId(),
                            'qty' => $qty,
                            'super_attribute' => $superAttribute
                        ];
                        $this->_cart->updateItem($itemId, new \Magento\Framework\DataObject($params));
                    } else {
                        $this->_cart->updateItem($itemId, ['qty' => $qty]);
                    }
                    $this->_cart->save();
                    $response['success'] = true;
                } catch (\Exception $e) {
                    $response['error'] = __('An error occurred while updating the cart.');
                }
            }
        }

        $resultPage = $this->_resultPageFactory->create();

        $couponBlockHtml = $resultPage->getLayout()
            ->createBlock('Railsformers\Checkout\Block\Cart\Coupon', 'checkout.cart.coupon')
            ->setTemplate('Railsformers_Checkout::cart/coupon.phtml')
            ->toHtml();
        $actionsBlockHtml = $resultPage->getLayout()
            ->createBlock('Railsformers\Checkout\Block\Cart\CartActions', 'checkout.cart.actions')
            ->setTemplate('Railsformers_Checkout::cart/actions.phtml')
            ->toHtml();

        $summaryHtml = $resultPage->getLayout()
                ->createBlock('Railsformers\Checkout\Block\Cart\Summary', 'checkout.cart.summary', ['data' => array()])
                ->setTemplate('Railsformers_Checkout::cart/summary.phtml')
                ->toHtml();

        $finalSummaryHtml = $resultPage->getLayout()
            ->createBlock('Railsformers\Checkout\Block\Checkout\FinalSummary', 'checkout.final.summary', ['data' => array()])
            ->setTemplate('Railsformers_Checkout::checkout/final_summary.phtml')
            ->toHtml();

        $itemsHtml = $resultPage->getLayout()
            ->createBlock('Railsformers\Checkout\Block\Cart\Items', 'checkout.cart.items')
            ->setTemplate('Railsformers_Checkout::cart/items.phtml')
            ->assign('couponBlockHtml', $couponBlockHtml)
            ->assign('actionsBlockHtml', $actionsBlockHtml)
            ->toHtml();
        
        return $this->_resultJsonFactory->setData(['success' => true,'summaryHtml' => $summaryHtml, 'finalSummaryHtml' => $finalSummaryHtml ,'itemsHtml' => $itemsHtml]);
    }
}