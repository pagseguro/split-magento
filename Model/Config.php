<?php
/**
 * PagBank Split Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\SplitMagento\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config - Configuration of Split.
 */
class Config
{
    /**
     * @const string
     */
    public const METHOD = 'pagbank_splitmagento';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Json
     */
    protected $json;

    /**
     * Construct.
     *
     * @param ScopeConfigInterface  $scopeConfig
     * @param Json                  $json
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Json $json
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->json = $json;
    }

    /**
     * Gets the Addtional Values Unserialize.
     *
     * @param string   $field
     * @param int|null $storeId
     *
     * @return array
     */
    public function getAddtionalValueUnSerialize($field, $storeId = null): array
    {
        $values = $this->getAddtionalValue($field, $storeId);

        $result = $this->json->unserialize($values);

        return is_array($result) ? $result : [];
    }

    /**
     * Gets the Addtional Values.
     *
     * @param string   $field
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getAddtionalValue($field, $storeId = null): ?string
    {
        $pathPattern = 'payment/%s/%s';

        return $this->scopeConfig->getValue(
            sprintf($pathPattern, self::METHOD, $field),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
