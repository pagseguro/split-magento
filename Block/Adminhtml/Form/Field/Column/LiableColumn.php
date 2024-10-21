<?php
/**
 * PagBank Split Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\SplitMagento\Block\Adminhtml\Form\Field\Column;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Ui\Component\Form\AttributeMapper;

/**
 * Class LiableColumn - Create Field Liable to Column.
 */
class LiableColumn extends Select
{
    /**
     * Sets name for input element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML.
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }

        return parent::_toHtml();
    }

    /**
     * Render Options.
     *
     * @return array
     */
    private function getSourceOptions(): array
    {
        $options = [
            [
                'label' => __('Send'),
                'value' => true,
            ],
            [
                'label' => __('Do not send'),
                'value' => false,
            ]
        ];

        return $options;
    }
}
