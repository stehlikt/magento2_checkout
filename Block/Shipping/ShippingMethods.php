<?php
namespace Railsformers\Checkout\Block\Shipping;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Railsformers\ShippingLevel\Model\ShippingLevel;
use Magento\Catalog\Model\CategoryRepository;

class ShippingMethods extends Template
{
    protected $_shippingConfig;
    protected $_rateRequest;
    protected $_quote;
    protected $_currency;
    protected $_shippingLevel;
    protected $_categoryRepository;

    public function __construct(
        Template\Context $context,
        \Magento\Shipping\Model\Config $shippingConfig,
        \Magento\Quote\Model\Quote\Address\RateRequest $rateRequest,
        \Magento\Checkout\Model\Session $checkoutSession,
        PriceCurrencyInterface $currency,
        ShippingLevel $shippingLevel,
        CategoryRepository $categoryRepository,
        array $data = []
    ) {
        $this->_rateRequest = $rateRequest;
        $this->_shippingConfig = $shippingConfig;
        $this->_quote = $checkoutSession->getQuote();
        $this->_currency = $currency;
        $this->_shippingLevel = $shippingLevel;
        $this->_categoryRepository = $categoryRepository;
        parent::__construct($context, $data);
    }

    public function getActiveShippingMethodsList()
    {
        $activeMethods = $this->_shippingConfig->getActiveCarriers();
        $activeMethodsList = [];
        $currencyCode = $this->_quote->getQuoteCurrencyCode();

        foreach ($activeMethods as $carrierCode => $carrierModel) {
            $carrierTitle = $this->_scopeConfig->getValue('carriers/' . $carrierCode . '/title');
            $shippingMethod = $this->_quote->getShippingAddress()->getShippingRateByCode($carrierCode . '_' . $carrierCode);

            // Check if the carrier is 'dhl2' and if maxLevel is not 4, skip it
            if ($carrierCode == 'dhl2' && !$this->isMaxLevelFour()) {
                continue;
            }

            if (($carrierCode == 'ppl' || $carrierCode == 'parcelshop' ) && $this->isMaxLevelFour()) {
                continue;
            }

            if ($shippingMethod) {
                $price = $this->_currency->format($shippingMethod->getPrice(), false);

                $activeMethodsList[] = [
                    'code' => $carrierCode,
                    'title' => $carrierTitle,
                    'price' => $price,
                    'price_raw' => $shippingMethod->getPrice(),
                ];
            } else {
                $activeMethodsList[] = [
                    'code' => $carrierCode,
                    'title' => $carrierTitle,
                    'price' => $this->_currency->format(0, false),
                    'price_raw' => 0,
                ];
            }
        }

        usort($activeMethodsList, function ($a, $b) {
            return ($a['price_raw'] < $b['price_raw']) ? -1: 1;
        });

        return $activeMethodsList;
    }

    private function isMaxLevelFour()
    {
        $maxLevel = 1;
        $shippingLevels = $this->_shippingLevel->getCollection();
        $levels = [];
        foreach ($shippingLevels as $sl) {
            $levels[$sl->getId()] = $sl->getPrice();
        }

        if ($this->_quote->getAllItems()) {
            foreach ($this->_quote->getAllItems() as $item) {
                foreach ($item->getProduct()->getCategoryIds() as $categoryId) {
                    try {
                        $category = $this->_categoryRepository->get($categoryId);
                        $shippingLevelId = $category->getShippingLevelId();
                        if ($shippingLevelId > 0 && isset($levels[$shippingLevelId])) {
                            if ($shippingLevelId > $maxLevel) {
                                $maxLevel = $shippingLevelId;
                            }
                        }
                    } catch(\Exception $e) {}
                }
            }
        }

        return $maxLevel == 4;
    }
}
