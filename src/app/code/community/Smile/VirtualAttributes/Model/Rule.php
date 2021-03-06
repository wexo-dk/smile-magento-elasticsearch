<?php
/**
 * Rule model for virtual attributes
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
 * @package   Smile_VirtualAttributes
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Apache License Version 2.0
 */
class Smile_VirtualAttributes_Model_Rule extends Smile_VirtualCategories_Model_Rule
{
    /**
     * Cache key for attributes rules
     */
    const CACHE_KEY_PREFIX = 'SMILE_VIRTUALATTRIBUTES_RULES';

    /**
     * Attributes already used into query generation.
     *
     * @var array
     */
    private $_usedAttributes = array();

    /**
     * Local cache for queries.
     *
     * @var array
     */
    private $_queryCache = array();

    /**
     * Retrieve list of the category ids used to build the condition
     *
     *  @return array
     */
    public function getUsedAttributeIds()
    {
        return $this->_usedAttributes;
    }

    /**
     * Append attribute id(s) to the list of attributes used to build the condition.
     *
     * @param array|int $attributeIds Attribute ids to add.
     *
     * @return Smile_VirtualAttributes_Model_Rule
     */
    public function addUsedAttributeIds($attributeIds)
    {
        if (!is_array($attributeIds)) {
            $attributeIds = array($attributeIds);
        }

        $attributeIds = array_filter($attributeIds);

        $this->_usedAttributes = array_unique(array_merge($this->_usedAttributes, $attributeIds));

        return $this;
    }

    /**
     * Retrieve an array of queries for each attribute option
     *
     * @return array
     *
     * @throws \Mage_Core_Exception
     */
    public function getAttributeValuesQueries()
    {
        $queries         = array();
        /** @var Mage_Eav_Model_Attribute $attribute */
        $attribute       = $this->getAttribute();
        $attributeValues = $attribute->getSource()->getAllOptions(false);

        foreach ($attributeValues as $attributeValue) {
            $optionId    = $attributeValue['value'];
            $arrayRule   = isset($attributeValue['rule']) ? $attributeValue['rule'] : null;
            $virtualRule = Mage::helper("smile_virtualattributes")->getFilterRule($attribute, $optionId, $arrayRule);

            $query = $virtualRule->getSearchQueryForOption($attribute, $optionId);
            if ($query) {
                $queries[$optionId] = '(' . $query . ')';
                $this->addUsedAttributeIds($virtualRule->getUsedAttributeIds());
            }
        }

        return array_filter($queries);
    }

    /**
     * Build product filter for a virtual attribute option.
     *
     * @param Mage_Eav_Model_Attribute $attribute The attribute
     * @param int                      $optionId  The option Id
     *
     * @return string
     */
    public function getSearchQueryForOption($attribute, $optionId)
    {
        $cacheKey  = $attribute->getId() . $optionId . Mage::app()->getStore()->getId();
        $cacheData = $this->getQueryFromCache($cacheKey);

        if (!$cacheData) {
            $this->_usedAttributes = array();
            $this->addUsedAttributeIds($attribute->getId());
            $query = $this->getConditions()->getSearchQuery(array());

            $this->cacheQuery($cacheKey, array($query, $this->_usedAttributes));

        } else {
            list($query, $this->_usedAttributes) = $cacheData;
        }

        return $query;
    }

    /**
     * Build product filter for several virtual attribute options.
     *
     * @param Mage_Eav_Model_Attribute $attribute The attribute
     * @param int[]                    $values    The applied values (option ids)
     *
     * @return string
     */
    public function getSearchQueryForMultipleOptions($attribute, $values)
    {
        $queries     = array();

        foreach ($values as $value) {
            $queries[] = $this->getSearchQueryForOption($attribute, (int) $value);
        }

        $queryString = implode(' OR ', $queries);

        return $queryString;
    }
}
