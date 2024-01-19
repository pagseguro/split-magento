<?php
/**
 * PagBank Split Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

declare(strict_types=1);

namespace PagBank\SplitMagento\Plugin\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use PagBank\PaymentMagento\Gateway\Http\Client\ApiClient;
use PagBank\PaymentMagento\Gateway\Http\Client\RefundClient as OriginalRefundClient;

class RefundClientPlugin
{

    /**
     * @var ApiClient
     */
    protected $api;

    /**
     * @param ApiClient $api
     */
    public function __construct(
        ApiClient $api
    ) {
        $this->api = $api;
    }

    /**
     * Plugin method to modify the placeRequest method of RefundClient.
     *
     * @param OriginalRefundClient $subject
     * @param callable $proceed
     * @param TransferInterface $transferObject
     * @return array
     */
    public function aroundPlaceRequest(
        OriginalRefundClient $subject,
        callable $proceed,
        TransferInterface $transferObject
    ) {
        $request = $transferObject->getBody();

        $paymentId = $request['payment_id'];

        $path = 'charges/'.$paymentId.'/cancel_';

        $data = $this->api->sendPostRequest($transferObject, $path, $request);

        $response = [];
        if (is_array($data)) {
            $response = array_merge(
                [
                    OriginalRefundClient::RESULT_CODE => (isset($data['id'])) ? 1 : 0,
                ],
                $data
            );
        }

        return $response;
    }
}
