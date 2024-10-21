<?php
/**
 * PagBank Split Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\SplitMagento\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use PagBank\SplitMagento\Block\Adminhtml\Form\Field\Column\FieldColumn;
use PagBank\SplitMagento\Block\Adminhtml\Form\Field\Column\LiableColumn;

/**
 * Class AddSplitOptions - Add Split Options to field.
 */
class AddSplitOptions extends AbstractFieldArray
{
    /**
     * @var FieldColumn
     */
    protected $fieldRenderer;

    /**
     * @var LiableColumn
     */
    protected $liableRenderer;

    /**
     * Prepare rendering the new field by adding all the needed columns.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _prepareToRender()
    {
        $this->addColumn('account_id', [
            'label'    => __('Account Id'),
            'class' => 'required-entry',
        ]);

        $this->addColumn('commision', [
            'label'    => __('Commission Percentual¹'),
            'class' => 'required-entry validate-digits validate-digits-range digits-range-1-100',
        ]);

        $this->addColumn('liable', [
            'label' => __('Liable²'),
            'renderer' => $this->getFieldLiableRenderer(),
        ]);

        $this->addColumn('charge_back', [
            'label' => __('Charge Back³'),
            'class' => 'required-entry validate-digits validate-digits-range digits-range-0-100',
        ]);

        $this->addColumn('transferring_interest', [
            'label' => __('Transferring Interest⁴'),
            'renderer' => $this->getFieldRenderer(),
        ]);

        $this->addColumn('transferring_shipping', [
            'label' => __('Transferring Shipping⁵'),
            'renderer' => $this->getFieldRenderer(),
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object.
     *
     * @param DataObject $row
     *
     * @throws LocalizedException
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $field = $row->getField();
        if ($field !== null) {
            $options['option_'.$this->getFieldRenderer()->calcOptionHash($field)] = 'selected="selected"';
            $options['option_'.$this->getFieldLiableRenderer()->calcOptionHash($field)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Create Block FieldColumn.
     *
     * @throws LocalizedException
     *
     * @return FieldColumn
     */
    private function getFieldRenderer()
    {
        if (!$this->fieldRenderer) {
            $this->fieldRenderer = $this->getLayout()->createBlock(
                FieldColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->fieldRenderer;
    }

    /**
     * Create Block FieldColumn.
     *
     * @throws LocalizedException
     *
     * @return FieldColumn
     */
    private function getFieldLiableRenderer()
    {
        if (!$this->liableRenderer) {
            $this->liableRenderer = $this->getLayout()->createBlock(
                LiableColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->liableRenderer;
    }
}
