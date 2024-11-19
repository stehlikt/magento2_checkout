<?php

namespace Railsformers\Checkout\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Magento\Quote\Model\QuoteManagement;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Psr\Log\LoggerInterface;

class SaveOrder extends Action
{
    protected $jsonFactory;
    protected $cartRepository;
    protected $quoteManagement;
    protected $customerRepository;
    protected $customerFactory;
    protected $storeManager;
    protected $resultRedirectFactory;
    protected $subscriberFactory;
    protected $addressInterfaceFactory;
    protected $addressRepository;
    protected $logger;
    protected $checkoutSession;


    public function __construct(
        Context                     $context,
        JsonFactory                 $jsonFactory,
        CartRepositoryInterface     $cartRepository,
        CheckoutSession             $checkoutSession,
        QuoteManagement             $quoteManagement,
        CustomerRepositoryInterface $customerRepository,
        CustomerFactory             $customerFactory,
        StoreManagerInterface       $storeManager,
        RedirectFactory             $resultRedirectFactory,
        SubscriberFactory           $subscriberFactory,
        AddressInterfaceFactory     $addressInterfaceFactory,
        AddressRepositoryInterface  $addressRepository,
        LoggerInterface             $logger
    )
    {
        $this->jsonFactory = $jsonFactory;
        $this->cartRepository = $cartRepository;
        $this->checkoutSession = $checkoutSession;
        $this->quoteManagement = $quoteManagement;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->addressInterfaceFactory = $addressInterfaceFactory;
        $this->addressRepository = $addressRepository;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        $result = new DataObject();

        try{
            $data = $this->getRequest()->getPostValue();

            $order_data = array();
            parse_str($data['orderData'], $order_data);

            $quoteId = $this->checkoutSession->getQuote()->getId();

            if ($quoteId) {
                $quote = $this->cartRepository->get($quoteId);

                $billingData = $order_data['billing'];

                $customer = $this->customerFactory->create();
                $customer->setWebsiteId(1);
                $customer->loadByEmail($billingData['email']);

                if (!$customer->getEntityId()) {
                    $quote->setCustomerIsGuest(true);
                    $quote->setCustomerEmail($billingData['email']);
                    $quote->setCustomerFirstname($billingData['firstname']);
                    $quote->setCustomerLastname($billingData['lastname']);
                } else {
                    $customer = $this->customerRepository->getById($customer->getEntityId());
                    $quote->assignCustomer($customer);


                    /*$this->setQuoteAddress($quote->getBillingAddress(), $billingData);
                    if (isset($order_data['same-as-billing']))
                        $this->setQuoteAddress($quote->getShippingAddress(), $billingData);
                    else
                        $this->setQuoteAddress($quote->getShippingAddress(), $shippingData);*/
                }

                $this->setQuoteAddress($quote->getBillingAddress(), $billingData);

                if (isset($order_data['save_new_billing_address']) && $order_data['save_new_billing_address'] == '1') {
                    $this->saveAddressToRepository($billingData, $quote->getCustomer());
                }

                if($order_data['shipping_method'] == 'parcelshop' && (isset($order_data['parcelshop-pickup-point']) && $order_data['parcelshop-pickup-point'] > ''))
                {
                    $parcelshopData = json_decode($order_data['parcelshop-pickup-point'], true);
                    $quote->getShippingAddress()->setFirstname('Parcelshop: ')
                        ->setLastname($parcelshopData['name'])
                        ->setCompany('')
                        ->setStreet($parcelshopData['street'])
                        ->setCity($parcelshopData['city'])
                        ->setPostcode($parcelshopData['zipCode'])
                        ->setTelephone($billingData['telephone'])
                        ->setCountryId($parcelshopData['country']);

                    $quote->setExtShippingInfo($order_data['parcelshop-pickup-point']);

                }
                else
                {

                    if (isset($order_data['same-as-billing'])) {
                        $this->setQuoteAddress($quote->getShippingAddress(), $billingData);

                    } else {
                        $shippingData = $order_data['shipping'];
                        $this->setQuoteAddress($quote->getShippingAddress(), $shippingData);

                        if (isset($order_data['save_new_shipping_address']) && $order_data['save_new_shipping_address'] == '1') {
                            $this->saveAddressToRepository($shippingData, $quote->getCustomer());
                        }

                    }
                }

                /*if (isset($order_data['newsletter']) && $order_data['newsletter'] == 1) {
                    $this->subscriberFactory->create()->subscribe($billingData['email']);
                }*/

                $quote->setCustomerNote($order_data['note']);


                $shippingAddress = $quote->getShippingAddress()->getShippingMethod();
                $quote->getShippingAddress()->setCollectShippingRates(true)
                    ->collectShippingRates()
                    ->setShippingMethod($shippingAddress);

                $quote->collectTotals();
                $quote->save();
                $order = $this->quoteManagement->submit($quote);

                if($order_data['shipping_method'] == 'parcelshop' && (isset($order_data['parcelshop-pickup-point']) && $order_data['parcelshop-pickup-point'] > ''))
                {
                    $parcelshopData = json_decode($order_data['parcelshop-pickup-point'], true);
                    if(isset($parcelshopData['dhlPsId'])) {
                        $order->setShippingDescription("ParcelShop - ParcelShop: " . $parcelshopData['name'] . ": " . $parcelshopData['street'] . ", " . $parcelshopData['city'] . ", ". $parcelshopData['zipCode'] . " (" . $parcelshopData['dhlPsId'] . ")");
                        $order->save();
                    }
                }

                $this->checkoutSession
                    ->setLastSuccessQuoteId($quote->getId())
                    ->setLastQuoteId($quote->getId());

                $this->checkoutSession
                    ->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());



                $result->setData('success', true);
                $result->setData('error', false);
                $payment_code = $order->getPayment()->getMethod();
                if($payment_code == 'comgate')
                {
                    $result->setData('redirect', '/comgate/payment/create');
                }
                else
                {
                    $result->setData('redirect', '/checkout/onepage/success');
                }

                //return $this->jsonFactory->create()->setData($result->getData());

                /*$resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('checkout/onepage/success');
                return $resultRedirect;*/

            } else {

                $result->setData('success', false);
                $result->setData('error', 'Chyba při uložení objednávky');

                //return $this->jsonFactory->create()->setData($result->getData());
            }
        } catch (\Exception $e)
        {
            $this->logger->error($e->getMessage());
            $result->setData([
                'success' => false,
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }

        return $this->jsonFactory->create()->setData($result->getData());
    }

    protected function saveAddressToRepository($data, $customer)
    {
        $address = $this->addressInterfaceFactory->create();
        $address->setCustomerId($customer->getId())
            ->setFirstname($data['firstname'])
            ->setLastname($data['lastname'])
            ->setStreet(explode(';',$data['street']))
            ->setCity($data['city'])
            ->setCountryId($data['country'])
            ->setPostcode($data['postcode'])
            ->setTelephone($data['telephone']);
        $this->addressRepository->save($address);
    }

    protected function setQuoteAddress($address, $data)
    {
        $address->setFirstname($data['firstname'])
            ->setLastname($data['lastname'])
            ->setTelephone($data['telephone'])
            ->setCompany($data['company'])
            ->setStreet($data['street'])
            ->setCity($data['city'])
            ->setPostcode($data['postcode'])
            ->setCountryId($data['country']);
            if(isset($data['vat']))
            {
                $address->setIc($data['vat'])
                    ->setDic($data['tax']);
            }

    }
}
