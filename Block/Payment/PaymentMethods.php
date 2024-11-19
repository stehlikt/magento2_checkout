<?php

namespace Railsformers\Checkout\Block\Payment;

use Magento\Framework\View\Element\Template;
use Railsformers\Checkout\Block\Shipping\ShippingMethods;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class PaymentMethods extends Template
{
    /**
     * Order Payment
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\Collection
     */
    protected $_orderPayment;

    /**
     * Payment Helper Data
     *
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentHelper;

    /**
     * Payment Model Config
     *
     * @var \Magento\Payment\Model\Config
     */
    protected $_paymentConfig;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Sales\Model\ResourceModel\Order\Payment\Collection $orderPayment
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param array $data
     */

    protected $_resourceConnection;
    protected $_shippingMethods;
    protected $_currency;
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\Payment\Collection $orderPayment,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        ShippingMethods $shippingMethods,
        PriceCurrencyInterface $currency,
        array $data = []
    ) {
        $this->_orderPayment = $orderPayment;
        $this->_paymentHelper = $paymentHelper;
        $this->_paymentConfig = $paymentConfig;
        $this->_resourceConnection = $resourceConnection;
        $this->_shippingMethods = $shippingMethods;
        $this->_currency = $currency;
        parent::__construct($context, $data);
    }

    /**
     * Get all payment methods
     *
     * @return array
     */
    public function getAllPaymentMethods()
    {
        return $this->_paymentHelper->getPaymentMethods();
    }

    /**
     * Get key-value pair of all payment methods
     * key = method code & value = method name
     *
     * @return array
     */
    public function getAllPaymentMethodsList()
    {
        return $this->_paymentHelper->getPaymentMethodList();
    }

    /**
     * Get active/enabled payment methods
     *
     * @return array
     */
    public function getActivePaymentMethods()
    {
        return $this->_paymentConfig->getActiveMethods();
    }

    /**
     * Get payment methods that have been used for orders
     *
     * @return array
     */
    public function getUsedPaymentMethods()
    {
        $collection = $this->_orderPayment;
        $collection->getSelect()->group('method');
        $paymentMethods[] = array('value' => '', 'label' => 'Any');
        foreach ($collection as $col) {
            $paymentMethods[] = array('value' => $col->getMethod(), 'label' => $col->getAdditionalInformation()['method_title']);
        }
        return $paymentMethods;
    }

    /**
     * Get key-value pair of active payment methods
     * key = method code & value = method name
     *
     * @return array
     */
    public function getActivePaymentMethodsList()
    {
        $activeMethods = $this->_paymentConfig->getActiveMethods();
        $activeMethodsList = [];
        foreach ($activeMethods as $method) {
            $activeMethodsList[] = [
                'code' => $method->getCode(),
                'title' => $method->getTitle(),
                'instructions' => $this->getPaymentInstructionsFromDatabase($method->getCode()),
                'price' => $method->getCode() == 'cashondelivery' ? $this->_currency->format($this->getCodPrice(), false) : NULL
            ];
        }

        return $activeMethodsList;
    } 

    /**
     * Get payment instructions from database
     *
     * @param string $paymentMethodCode
     * @return string
     */
    public function getPaymentInstructionsFromDatabase($paymentMethodCode)
    {
        $connection = $this->_resourceConnection->getConnection();
        $tableName = $connection->getTableName('core_config_data');
        $select = $connection->select()->from($tableName, 'value')->where('path = ?', 'payment/'.$paymentMethodCode.'/instructions');
        $instructions = $connection->fetchOne($select);

        return $instructions ? $instructions : '';
    }

    public function getCodPrice(): int
    {
        $activeShippingMethods = $this->_shippingMethods->getActiveShippingMethodsList();
        $codPrices = array();

        $connection = $this->_resourceConnection->getConnection();
        $tableName = $connection->getTableName('core_config_data');

        foreach($activeShippingMethods as $shipping)
        {
            $select = $connection->select()->from($tableName,'value')->where('path = ?','carriers/'.$shipping['code'].'/codprice');
            $price = $connection->fetchOne($select);
            if($price)
                $codPrices[] = $price;
        }
        if(!empty($_SESSION['cod_prices']))
            return max($_SESSION['cod_prices']);
        else
            return count($codPrices) > 0 ? max($codPrices) : 0;
    }
}
