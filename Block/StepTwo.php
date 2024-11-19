<?php
namespace Railsformers\Checkout\Block;

class StepTwo extends \Magento\Framework\View\Element\Template
{
    protected $_cart;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Cart $cart,
        array $data = []
    ) {
        $this->_cart = $cart;
        parent::__construct($context, $data);
    }

    public function isCartEmpty()
    {
        return $this->_cart->getQuote()->getItemsCount() == 0;
    }
}
