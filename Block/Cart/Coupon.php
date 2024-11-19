<?php

namespace Railsformers\Checkout\Block\Cart;


class Coupon extends \Magento\Checkout\Block\Cart\AbstractCart
{

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    )
    {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
    }

    public function getCouponCode()
    {
        return $this->getQuote()->getCouponCode();
    }

    public function isCouponApplied()
    {
        return $this->getQuote()->getAppliedRuleIds() ? true : false;
    }
}
