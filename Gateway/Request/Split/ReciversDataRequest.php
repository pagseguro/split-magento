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
use PagBank\SplitMagento\Gateway\Request\Split\BaseDataRequest;
use PagBank\PaymentMagento\Gateway\Request\ChargesDataRequest;
use PagBank\SplitMagento\Model\Config as SplitConfig;

/**
 * Class Recivers Data Request - Structure of payment for Split.
 */
class ReciversDataRequest implements BuilderInterface
{
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
        $hasSplit = 1;

        /** @var PaymentDataObject $paymentDO * */
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order $order * */
        $order = $paymentDO->getOrder();

        $storeId = $order->getStoreId();

        $status = $this->splitConfig->getAddtionalValue('use_split', $storeId);

        if ($status) {
            $secondarys = $this->getSecondarys($buildSubject);
            $primary = $this->getPrimary($secondarys, $buildSubject);
            
            if (!$secondarys) {
                return $result;
            }

            foreach ($secondarys as $subAccounts) {
                foreach ($subAccounts[BaseDataRequest::RECEIVER_ACCOUNT] as $subs) {
                    if (isset($subs[BaseDataRequest::RECEIVER_ACCOUNT_ID]) && !$subs[BaseDataRequest::RECEIVER_ACCOUNT_ID]) {
                        $hasSplit = 0;
                    }
                }
            }

            if ($hasSplit) {
                $recivers = array_merge($primary, $secondarys);
                $recivers = $this->normalizeNodeConfiguration($recivers);
                $recivers = $this->normalizeReceivers($recivers);

                $result[ChargesDataRequest::CHARGES][] = [
                    BaseDataRequest::SPLITS => [
                        BaseDataRequest::SPLITS_METHOD => 'FIXED',
                        BaseDataRequest::SPLITS_RECEIVERS => $recivers
                    ]
                ];
            }

        }

        return $result;
    }

    /**
     * Normalize Node Configuration.
     *
     * @param array  $receivers
     *
     * @return array
     */
    public function normalizeNodeConfiguration($receivers)
    {
        if (!isset($receivers[1]['configuration'])) {
            return $receivers;
        }

        $secondNodeConfig = $receivers[1]['configuration'];

        $newPercentage = 100 - $secondNodeConfig['chargeback']['charge_debtor']['percentage'];

        $receivers[0]['configuration'] = [
            "liable" => !$secondNodeConfig['liable'],
            "chargeback" => [
                "charge_debtor" => [
                    "percentage" => $newPercentage
                ]
            ]
        ];

        if (isset($receivers[0]['configuration']['chargeback']['charge_debtor']['percentage']) &&
            $receivers[0]['configuration']['chargeback']['charge_debtor']['percentage'] < 0) {
            $receivers[0]['configuration']['chargeback']['charge_debtor']['percentage'] =
                abs($receivers[0]['configuration']['chargeback']['charge_debtor']['percentage']);
        }

        return $receivers;
    }

    /**
     * Normalize Recivers.
     *
     * @param array $receivers
     * @return array
     */
    public function normalizeReceivers($receivers)
    {
        $normalized = [];
    
        foreach ($receivers as $receiver) {
            $accountId = $receiver['account']['id'];
            $amountValue = $receiver['amount']['value'];
            $configuration = isset($receiver['configuration']) ? $receiver['configuration'] : null;
    
            if (isset($normalized[$accountId])) {
                $normalized[$accountId]['amount']['value'] += $amountValue;
                $normalized[$accountId]['configuration'] = $configuration;
            } else {
                $normalized[$accountId] = [
                    'account' => ['id' => $accountId],
                    'amount' => ['value' => $amountValue],
                    'configuration' => $configuration,
                ];
            }
        }

        return array_values($normalized);
    }

    /**
     * Get Secondarys.
     *
     * @param array $buildSubject
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getSecondarys(array $buildSubject)
    {
        /** @var PaymentDataObject $paymentDO * */
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order $order * */
        $order = $paymentDO->getOrder();

        $storeId = $order->getStoreId();

        $secondary = [];

        $dataSource = $this->splitConfig->getAddtionalValue('data_source', $storeId);

        if ($dataSource === 'simple') {
            $subSellers = $this->splitConfig->getAddtionalValueUnSerialize('sub_sellers', $storeId);

            foreach ($subSellers as $subSellerConfig) {
                $amountForSubSeller = $this->getAmountToSubSeller($buildSubject, $subSellerConfig['account_id']);
                $secondary[] = [
                    BaseDataRequest::RECEIVER_ACCOUNT => [
                        BaseDataRequest::RECEIVER_ACCOUNT_ID => $subSellerConfig['account_id'],
                    ],
                    BaseDataRequest::RECEIVER_AMOUNT => [
                        BaseDataRequest::RECEIVER_AMOUNT_VALUE => $this->config->formatPrice($amountForSubSeller),
                    ],
                    BaseDataRequest::RECEIVER_CONFIGURATION => [
                        BaseDataRequest::RECEIVER_LIABLE => (bool) $subSellerConfig['liable'],
                        BaseDataRequest::RECEIVER_CHARGEBACK => [
                            BaseDataRequest::RECEIVER_CHARGE_DEBTOR => [
                                BaseDataRequest::RECEIVER_CHARGE_PERCENTAGE => (int) $subSellerConfig['charge_back']
                            ]
                        ],
                    ],
                ];
            }
        }

        return $secondary;
    }

    /**
     * Get Primary Seller.
     *
     * @param array $recivers
     * @param array $buildSubject
     *
     * @return array|null
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getPrimary($recivers, $buildSubject)
    {
        $amountComissions = 0;

        $primary = [];

        /** @var PaymentDataObject $paymentDO **/
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order $order **/
        $order = $paymentDO->getOrder();

        $grandTotal = $order->getGrandTotalAmount();

        $storeId = $order->getStoreId();

        foreach ($recivers as $subSeller) {
            $amountComissions += $subSeller[BaseDataRequest::RECEIVER_AMOUNT][BaseDataRequest::RECEIVER_AMOUNT_VALUE];
        }

        $primaryAmount = $grandTotal - ($amountComissions/100);

        $enviroment = $this->config->getEnvironmentMode($storeId);

        $keyAccount = sprintf('account_id_%s', $enviroment);

        if ($primaryAmount) {
            $primary[] = [
                BaseDataRequest::RECEIVER_ACCOUNT => [
                    BaseDataRequest::RECEIVER_ACCOUNT_ID => $this->config->getAddtionalValue($keyAccount, $storeId),
                ],
                BaseDataRequest::RECEIVER_AMOUNT => [
                    BaseDataRequest::RECEIVER_AMOUNT_VALUE => $this->config->formatPrice($primaryAmount),
                ],
            ];
        }

        return $primary;
    }

    /**
     * Get Amount to Sub Seller.
     *
     * @param array $buildSubject
     * @param string $subSeller
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getAmountToSubSeller($buildSubject, $subSeller)
    {
        /** @var PaymentDataObject $paymentDO **/
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order $order **/
        $order = $paymentDO->getOrder();
        
        /** @var InfoInterface $payment **/
        $payment = $paymentDO->getPayment();

        /** @var OrderAdapterFactory $orderAdapter **/
        $orderAdapter = $this->orderAdapterFactory->create(
            ['order' => $payment->getOrder()]
        );

        $storeId = $order->getStoreId();
 
        $grandTotal = $order->getGrandTotalAmount();

        $shipping = $orderAdapter->getShippingAmount();

        $pagbankInterest = $orderAdapter->getPagbankInterestAmount();

        $transferInstallments = false;

        $transferShipping = false;

        $commision = 0;

        $subSellers = $this->splitConfig->getAddtionalValueUnSerialize('sub_sellers', $storeId);

        foreach ($subSellers as $subSellerConfig) {
            if ($subSellerConfig['account_id'] === $subSeller) {
                $commision = $subSellerConfig['commision'];
                $transferInstallments = $subSellerConfig['transferring_interest'];
                $transferShipping = $subSellerConfig['transferring_shipping'];
            }
        }

        $baseCalc = $grandTotal - $shipping - $pagbankInterest;

        if ($transferInstallments) {
            // Proportional interest value according to the number of subsellers
            $pagbankInterest = $pagbankInterest/count($subSellers);
            $baseCalc += $pagbankInterest;
        }

        if ($transferShipping) {
            // Proportional shipping value according to the number of subsellers
            $shipping = $shipping/count($subSellers);
            $baseCalc += $shipping;
        }

        $amount = $baseCalc;

        if ($commision) {
            $amount = $baseCalc * ($commision/100);
        }

        return $amount;
    }
}
