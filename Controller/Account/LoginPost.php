<?php

namespace Railsformers\Checkout\Controller\Account;

class LoginPost extends \Magento\Customer\Controller\Account\LoginPost
{
    public function execute()
    {
        parent::execute();

        $request = $this->getRequest();
        $resultRedirect = $this->resultRedirectFactory->create();

        if($request->getParam('checkout'))
        {
            $loginRedirect = $this->_url->getUrl('checkout');
        }
        else
        {
            $loginRedirect = $this->_url->getUrl('customer/account');
        }

        $resultRedirect->setPath($loginRedirect);

        return $resultRedirect;
    }
}