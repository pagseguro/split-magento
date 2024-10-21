<?php
/**
 * PagBank Split Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\SplitMagento\Gateway\Request\Split;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use PagBank\PaymentMagento\Gateway\Config\Config;
use PagBank\PaymentMagento\Gateway\Config\ConfigCc;
use PagBank\PaymentMagento\Gateway\Data\Order\OrderAdapterFactory;
use PagBank\PaymentMagento\Gateway\Request\ChargesDataRequest;
use PagBank\SplitMagento\Model\Config as SplitConfig;

/**
 * Class Base Data Request - Structure of payment for Split.
 */
class BaseDataRequest implements BuilderInterface
{
    /**
     * Splits block name.
     */
    public const SPLITS = 'splits';

    /**
     * Splits Method block name.
     */
    public const SPLITS_METHOD = 'method';

    /**
     * Splits Receivers block name.
     */
    public const SPLITS_RECEIVERS = 'receivers';

    /**
     * Receiver Account block name.
     */
    public const RECEIVER_ACCOUNT = 'account';

    /**
     * Receiver Configuration block name.
     */
    public const RECEIVER_CONFIGURATION = 'configuration';

    /**
     * Receiver Liable block name.
     */
    public const RECEIVER_LIABLE = 'liable';

    /**
     * Receiver Charge Back block name.
     */
    public const RECEIVER_CHARGEBACK = 'chargeback';

    /**
     * Receiver Charge Debtor block name.
     */
    public const RECEIVER_CHARGE_DEBTOR = 'charge_debtor';

    /**
     * Receiver Charge Percentage block name.
     */
    public const RECEIVER_CHARGE_PERCENTAGE = 'percentage';

    /**
     * Receiver Account Id block name.
     */
    public const RECEIVER_ACCOUNT_ID = 'id';

    /**
     * Receiver Amount block name.
     */
    public const RECEIVER_AMOUNT = 'amount';

    /**
     * Receiver Amount Value block name.
     */
    public const RECEIVER_AMOUNT_VALUE = 'value';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigCc
     */
    protected $configCc;

    /**
     * @var SplitConfig
     */
    protected $splitConfig;

    /**
     * @var OrderAdapterFactory
     */
    protected $orderAdapterFactory;

    /**
     * @param Config              $config
     * @param ConfigCc            $configCc
     * @param SplitConfig         $splitConfig
     * @param OrderAdapterFactory $orderAdapterFactory
     */
    public function __construct(
        Config $config,
        ConfigCc $configCc,
        SplitConfig $splitConfig,
        OrderAdapterFactory $orderAdapterFactory
    ) {
        $this->config = $config;
        $this->configCc = $configCc;
        $this->splitConfig = $splitConfig;
        $this->orderAdapterFactory = $orderAdapterFactory;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function build(array $buildSubject)
    {
        $result = [];
        /** @var PaymentDataObject $paymentDO * */
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order $order * */
        $order = $paymentDO->getOrder();

        $storeId = $order->getStoreId();

        $status = $this->splitConfig->getAddtionalValue('use_split', $storeId);

        if ($status) {
            $result[ChargesDataRequest::CHARGES][] = [
                self::SPLITS => [
                    self::SPLITS_METHOD => 'FIXED',
                ]
            ];
        }

        return $result;
    }
}
