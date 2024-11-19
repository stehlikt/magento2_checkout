<?php

namespace Railsformers\Checkout\Block\Checkout;

class BillingAndShippingForm extends \Magento\Framework\View\Element\Template
{
    protected $customerSession;
    protected $addressRepository;
    protected $searchCriteriaBuilder;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->addressRepository = $addressRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context, $data);
    }

    /**
     * Vrátí seznam adres pro přihlášeného uživatele.
     *
     * @return \Magento\Customer\Api\Data\AddressInterface[]
     */
    public function getCustomerAddresses()
    {
        $customer = $this->customerSession->getCustomer();
        if ($customer) {
            try {
                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilter('parent_id', $customer->getId())
                    ->create();
                $addresses = $this->addressRepository->getList($searchCriteria);
                return $addresses->getItems();
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage());
            }
        }
        return [];
    }

    public function getCustomerEmail()
    {
        $customer = $this->customerSession->getCustomer();
        return $customer ? $customer->getEmail() : null;
    }
}