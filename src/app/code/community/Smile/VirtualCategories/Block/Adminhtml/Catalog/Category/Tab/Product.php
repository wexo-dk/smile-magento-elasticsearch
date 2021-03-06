<?php
/**
 * Virtual categories admin tab.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Searchandising Suite to newer
 * versions in the future.
 *
 * This work is a fork of Johann Reinke <johann@bubblecode.net> previous module
 * available at https://github.com/jreinke/magento-elasticsearch
 *
 * @category  Smile
 * @package   Smile_VirtualCategories
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2013 Smile
 * @license   Apache License Version 2.0
 */
class Smile_VirtualCategories_Block_Adminhtml_Catalog_Category_Tab_Product extends Mage_Adminhtml_Block_Catalog_Form
{
    /**
     * Get the current edit category.
     *
     * @return Mage_Catalog_Model_Category
     */
    public function getCategory()
    {
        if (!$this->_category) {
            $this->_category = Mage::registry('category');
        }
        return $this->_category;
    }

    /**
     * Create the form
     *
     * @return Smile_VirtualCategories_Block_Adminhtml_Catalog_Category_Tab_Product
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('virtual_');

        $this->_prepareCategoryTypeSelector($form)
            ->_prepareVirtualRuleFieldset($form)
            ->_prepareLegacyProductSelector($form);

        $form->setFieldNameSuffix('general');
        $form->addValues($this->getCategory()->getData());

        $this->setForm($form);

        $this->setTemplate('smile/virtualcategories/category/product_select_form.phtml');

        return parent::_prepareForm();
    }

    /**
     * Create the category type selector fieldset.
     *
     * @param Varien_Data_Form $form Current form.
     *
     * @return Smile_VirtualCategories_Block_Adminhtml_Catalog_Category_Tab_Product
     */
    private function _prepareCategoryTypeSelector(Varien_Data_Form $form)
    {
        $fieldset = $form->addFieldset(
            'base_fieldset',
            array('legend' => $this->__('Category type'), 'class' => 'fieldset-wide')
        );

        $typeSelectorFieldConfiguration = array(
            'name' => 'is_virtual',
            'label' => $this->__('Enable virtual category'),
            'title' => $this->__('Enable virtual category'),
            'required' => true,
            'options' => array('0' => $this->__('No'), '1' => $this->__('Yes'))
        );

        $typeSelector = $fieldset->addField('is_virtual', 'select', $typeSelectorFieldConfiguration);

        $this->_prepareRootCategorySelector($fieldset);

        $this->setSelectorHtmlId($typeSelector->getHtmlId());

        return $this;
    }

    /**
     * Create the category rule admin fieldset.
     *
     * @param Varien_Data_Form $form Current form.
     *
     * @return Smile_VirtualCategories_Block_Adminhtml_Catalog_Category_Tab_Product
     */
    private function _prepareVirtualRuleFieldset(Varien_Data_Form $form)
    {
        $url = $this->getUrl('*/promo_catalog/newConditionHtml', array('form' => 'virtual_virtual_fieldset'));

        $virtualProductsGrid = $this->getVirtualProductsGrid();
        $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
            // Legacy rule_fieldset.phtml is used by another module
            // @TODO v1.4 refactor this
            ->setTemplate('smile/virtualcategories/category/preview/rule_fieldset.phtml')
            ->setNewChildUrl($url)
            ->setPreviewUrl($virtualProductsGrid->getGridUrl())
            ->setVirtualProductsGridHtml($virtualProductsGrid->toHtml());

        $fieldset = $form->addFieldset(
            'virtual_fieldset',
            array('legend' => $this->__('Virtual condition'), 'class' => 'fieldset-wide')
        )->setRenderer($renderer);

        $field = $fieldset->addField(
            'conditions',
            'text',
            array(
                'name' => 'conditions',
                'label' => Mage::helper('catalogrule')->__('Conditions'),
                'title' => Mage::helper('catalogrule')->__('Conditions'),
                'required' => true,
            )
        );

        $this->setVirtualRuleFieldsetHtmlId($fieldset->getHtmlId());

        $rule = Mage::getModel('smile_virtualcategories/rule');

        if ($this->getCategory()->getVirtualRule()) {
            $rule = $this->getCategory()->getVirtualRule();
        }

        $rule->setForm($form);
        $rule->getConditions()->setJsFormObject('virtual_virtual_fieldset');

        $field->setRule($rule)
            ->setRenderer(Mage::getBlockSingleton('rule/conditions'));

        return $this;
    }

    /**
     * Append the category product grid.
     *
     * @param Varien_Data_Form $form Current form.
     *
     * @return Smile_VirtualCategories_Block_Adminhtml_Catalog_Category_Tab_Product
     */
    private function _prepareLegacyProductSelector(Varien_Data_Form $form)
    {
        $fieldset = $form->addFieldset(
            'product_selection',
            array('legend' => $this->__('Product selection'), 'class' => 'fieldset-wide')
        );

        $fieldset->addField('pgold', 'hidden', array('name' => 'pgold'))
            ->setAfterElementHtml($this->getProductsGrid());

        $this->setProductFieldsetHtmlId($fieldset->getHtmlId());

        return $this;
    }

    /**
     * Retrieve Virtual category products grid
     *
     * @return Smile_VirtualCategories_Block_Adminhtml_Catalog_Category_Tab_Preview_Grid
     */
    public function getVirtualProductsGrid()
    {
        return $this->getLayout()->createBlock(
            'smile_virtualcategories/adminhtml_catalog_category_tab_preview_grid',
            'virtual.category.product.grid'
        );
    }

    /**
     * Append Virtual category Root category field selector
     *
     * @param \Varien_Data_Form_Element_Fieldset $fieldset The fieldset
     */
    private function _prepareRootCategorySelector(Varien_Data_Form_Element_Fieldset $fieldset)
    {
        $virtualCategoryRootRendererBlock = 'smile_virtualcategories/adminhtml_catalog_category_tab_product_renderer_rootCategory';

        $useCustomCategoryRootFieldConfiguraton = array(
            'name' => 'use_custom_root_category',
            'label' => $this->__('Use Custom root category'),
            'title' => $this->__('Use Custom root category'),
            'required' => true,
            'options' => array('0' => $this->__('No'), '1' => $this->__('Yes'))
        );

        $fieldset->addField('use_custom_root_category', 'select', $useCustomCategoryRootFieldConfiguraton);

        $fieldset->addField(
            'custom_root_category',
            'hidden',
            array(
                'name'    => 'custom_root_category',
                'label'   => $this->__("Custom root category"),
                'title'   => $this->__("Custom root category"),
                'unique'  =>'true',
                'comment' => '<p class="note"><span>'.
                    $this->__('Category filter will be displayed related to this category').
                    '</span></p>',
            ),
            'use_custom_root_category'
        )->setRenderer(Mage::getBlockSingleton($virtualCategoryRootRendererBlock));

        $fieldset->getForm()->getElement('custom_root_category')->setFieldsetId($fieldset->getId());
    }
}
