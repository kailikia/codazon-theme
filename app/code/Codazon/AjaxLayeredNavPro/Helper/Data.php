<?php
/**
* Copyright © 2018 Codazon. All rights reserved.
* See COPYING.txt for license details.
*/

namespace Codazon\AjaxLayeredNavPro\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $filterManager;
    
    const ENABLE = 'codazon_ajaxlayerednavpro/general/enable';
    const ENABLE_PRICE_SLIDER = 'codazon_ajaxlayerednavpro/general/enable_price_slider';
    const ENABLE_FILTER_BY_RATING = 'codazon_ajaxlayerednavpro/rating_builder/enable_filter';
    const ENABLE_SORT_BY_RATING = 'codazon_ajaxlayerednavpro/rating_builder/enable_sort';
    const RATING_CODE = 'rating';
    const RATING_CODE_PATH = 'codazon_ajaxlayerednavpro/rating_builder/rating_code'; 
    const RATING_FILTER_LABEL = 'codazon_ajaxlayerednavpro/rating_builder/filter_label';
    const RATING_SORT_LABEL = 'codazon_ajaxlayerednavpro/rating_builder/sort_label';
    const RATING_FILTER_TYPE_PATH = 'codazon_ajaxlayerednavpro/rating_builder/filter_type';
    const AVG_RATING_PERCENT = 'avg_percent';
   
    protected $enable;
    
    protected $layout;
    
    protected $ratingCode;
    
    protected $block = \Magento\LayeredNavigation\Block\Navigation\FilterRenderer::class;
    
    protected $swatchBlock = \Magento\Swatches\Block\LayeredNavigation\RenderLayered::class;
    
    protected $swatchHelper;
    
    protected $objectManager;
    
    protected $_filters;
    
    protected $_enableMultiSelect;
    
    protected $_enableCategoryMultiSelect;
        
    protected $ratingFilerFlag;
    
    protected $ratingFilter;
    
    protected $isRatingLayered;
    
    protected $ratingFilterType;
    
    protected function getSwatchHelper() {
        if (null === $this->swatchHelper) {
            $this->swatchHelper = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Magento\Swatches\Helper\Data'
            );
        }
        return $this->swatchHelper;
    }
    
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Swatches\Helper\Data $swatchHelper
    ) {
        parent::__construct($context);
        $this->swatchHelper = $swatchHelper;
        $this->layout = $layout;
        $this->filterManager = $filterManager;
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }
    
    public function getLayout()
    {
        if (null === $this->layout) {
            $this->layout = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Framework\View\LayoutInterface');
        }
        return $this->layout;
    }
    
    public function getFilterManager()
    {
        return $this->filterManager;
    }

    public function getConfig($path)
    {
        return $this->scopeConfig->getValue($path, 'store');
    }
    
    public function setRatingFilterFlag($value)
    {
        $this->ratingFilerFlag = $value;
    }
    
    public function getRatingFilterFlag()
    {
        return $this->ratingFilerFlag;
    }
    
    
    public function enableAjaxLayeredNavigation()
    {
        if ($this->enable === null) {
            $this->enable = (bool)$this->scopeConfig->getValue(self::ENABLE, 'store');
        }
        return $this->enable;
    }
    
    public function enablePriceSlider()
    {
        return $this->scopeConfig->getValue(self::ENABLE_PRICE_SLIDER, 'store');
    }
    
    public function extractExtraOptions($attributeObject)
    {
        if ($extraOptions = $attributeObject->getData('extra_options')) {
            $extraOptions = json_decode($extraOptions, true);
            if (!empty($extraOptions['custom_style'])) {
                $attributeObject->addData([
                    'custom_style' => $extraOptions['custom_style']
                ]);
            }
        }
    }
    
    public function getFilterHtml($filter, $customStyle)
    {
        $block = $this->block;
        $isSwatchAttribute = $this->swatchHelper->isSwatchAttribute($filter->getAttributeModel());
        if ($isSwatchAttribute && ($customStyle == 'checkbox')) {
            $block = $this->swatchBlock;
        }
        
        $attributeModel = $filter->getAttributeModel();
        if (($customStyle == 'slider') && ($attributeModel->getFrontendInput() == 'price')) {
            $customStyle = 'price-slider';
        }
        return $this->getLayout()->createBlock($block)
            ->setTemplate('Codazon_AjaxLayeredNavPro::layer/custom-style/'.$customStyle.'.phtml')
            ->setOptionsFilter($filter)
            ->setSwatchFilter($filter)
            ->setIsSwatchAttribute($isSwatchAttribute)
            ->setData('custom_style', $customStyle)
            ->toHtml();
    }
    
    public function getItemsValuesRange($filter)
    {
        $filterItems = $filter->getItems();
        $items = [];
        if (count($filterItems)) {
            $i = 0;
            foreach ($filterItems as $filterItem) {
                $items[$i] = [
                    'value'     => $filterItem->getValue(),
                    'label'     => $filterItem->getLabel(),
                ];
                $i++;
            }
        }
        return $items;
    }
    
    public function getFilterAction($filter)
    {
        $query = $this->_request->getQueryValue();
        $code = $filter->getRequestVar();
        $query[$code] = null;
        $query['p'] = null;
        $action = $this->_urlBuilder->getUrl('*/*/*', [
            '_current'      => true,
            '_use_rewrite'  => true,
            '_query'        => $query,
        ]);
        return $action;
    }
    
    public function getMinMaxOfRange($filter)
    {
        $filterItems = $filter->getItems();
        $code = $filter->getRequestVar();
        $values = $this->_request->getParam($code);
        $items = [];
        $count = count($filterItems);
        foreach ($filterItems as $filterItem) {
            $items[] = $filterItem->getValue();
        }
        $min = 0;
        $max = 0;
        if ($values) {
            $values = explode(',', $values);
            for ($i = 0; $i < $count; $i++) {
                if (in_array($items[$i], $values)) {
                    $min = $i; break;
                }
            }
            for ($i = ($count - 1); $i >= 0; $i--) {
                if (in_array($items[$i], $values)) {
                    $max = $i; break;
                }
            }
        } else {
            if ($count) {
                return [0, $count - 1];
            }
        }
        return [$min, $max];
    }
    
    public function getFilters()
    {
        if (null === $this->_filters) {
            if ($this->_request->getFullActionName() === 'catalogsearch_result_index') {
                $this->_filters = $this->objectManager->get('Magento\LayeredNavigation\Block\Navigation\Search')->getFilters();
            } else {
                $this->_filters = $this->objectManager->get('Magento\LayeredNavigation\Block\Navigation\Category')->getFilters();
            }
        }
        return $this->_filters;
    }
    
    public function getBeforeApplyFacetedData($collection, $attribute, $currentFilter = null)
    {
        $cloneCollection = clone $collection;
        $cloneFilterBuilder = clone $this->objectManager->get(\Magento\Framework\Api\FilterBuilder::class);
        $cloneCollection->setFilterBuilder($cloneFilterBuilder);
        
        $cloneSearchCriteriaBuilder = clone $this->objectManager->get(\Magento\Framework\Api\Search\SearchCriteriaBuilder::class);
        $cloneCollection->setSearchCriteriaBuilder($cloneSearchCriteriaBuilder);        
        
        $attributeCode = $attribute->getAttributeCode();
        foreach ($this->getFilters() as $filter) {
            if ($filter->getRequestVar() != $attributeCode) {
                if (method_exists($filter, 'applyToCollection')) {
                    $filter->applyToCollection($cloneCollection, $this->_request, $filter->getRequestVar());
                }
            }
        }
        if ($currentFilter) {
            $currentFilter->setBeforeApplyCollection($cloneCollection);
        }
        if ($this->isRatingLayered()) {
            $clone2 = clone $cloneCollection;
            $facetedData = $cloneCollection->getFacetedData($attribute->getAttributeCode());
            $connection = $clone2->getConnection();
            foreach ($facetedData as $value => $option) {
                if ($facetedData[$value]['count'] > 0) {
                    $clone = clone $clone2;
                    $facetedData[$value]['count'] = $connection->fetchOne($clone->addFieldToFilter($attributeCode, $value)->getSelectCountSql());
                }
            }
            return $facetedData;
        }
        return $cloneCollection->getFacetedData($attribute->getAttributeCode());
    }
    
    public function enableMultiSelect()
    {
        if (null === $this->_enableMultiSelect) {
            $this->_enableMultiSelect = ((bool)$this->getConfig('codazon_ajaxlayerednavpro/general/enable_multiselect')) && ((bool)$this->enableAjaxLayeredNavigation());
        }
        return $this->_enableMultiSelect;
    }
    
    public function enableCategoryMultiSelect()
    {
        if ($this->_enableCategoryMultiSelect === null) {
            $this->_enableCategoryMultiSelect = $this->scopeConfig->getValue('codazon_ajaxlayerednavpro/general/category_multiselect', 'store')
                && $this->enableAjaxLayeredNavigation();
        }
        return $this->_enableCategoryMultiSelect;
    }
    
    public function enableFilterByRating()
    {
        return (bool) $this->getConfig(self::ENABLE_FILTER_BY_RATING);
    }
    
    public function enableSortByRating()
    {
        return (bool) $this->getConfig(self::ENABLE_SORT_BY_RATING);
    }
    
    public function getRatingCode()
    {
        if ($this->ratingCode === null) {
            $this->ratingCode = $this->getConfig(self::RATING_CODE_PATH) ? : self::RATING_CODE;
        }
        return $this->ratingCode;
    }
    
    public function getRatingFilterLabel()
    {
        return $this->getConfig(self::RATING_FILTER_LABEL) ? : __('Rating');
    }
    
    public function getRatingSortLabel()
    {
        return $this->getConfig(self::RATING_SORT_LABEL) ? : __('Rating');
    }
    
    public function isRatingLayered()
    {
        if ($this->isRatingLayered === null) {
            $this->isRatingLayered = $this->_request->getParam($this->getRatingCode(), false);
        }
        return $this->isRatingLayered;
    }
    public function isRatingFilter($filter)
    {
        return $filter->getRequestVar() == $this->getRatingCode();
    }
    
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    public function getFilterByRatingHtml($filter)
    {
        $customStyle = $this->getConfig(self::RATING_FILTER_TYPE_PATH);
        $block = $this->block;
        return $this->getLayout()->createBlock($block)
            ->setTemplate('Codazon_AjaxLayeredNavPro::layer/rating/'.$customStyle.'.phtml')
            ->setOptionsFilter($filter)
            ->setData('custom_style', $customStyle)
            ->toHtml();
    }
    
    public function getRatingFilterType()
    {
        if ($this->ratingFilterType === null) {
            $type = $this->getConfig(self::RATING_FILTER_TYPE_PATH) ? : 'link-up';
            $type = explode('-', $type);
            $this->ratingFilterType = empty($type[1]) ? 'up' : $type[1];
        }
        return $this->ratingFilterType;
    }
    
    public function attachRatingAvgPercentFieldToCollection($productCollection)
    {
        if ($productCollection->hasFlag('attach_rating_avg_percent_field')) {
            return $productCollection;
        }        
        $productCollection->setFlag('attach_rating_avg_percent_field', 1);
        $connection = $productCollection->getConnection();
        $storeId = $productCollection->getStoreId();
        $select = $connection->select()->from(['product' => $productCollection->getTable('catalog_product_entity')], ['entity_pk_value' => 'entity_id'])
                ->joinLeft(
                    ['rt' => $connection->select()
                        ->from(['rova' => $productCollection->getTable('rating_option_vote_aggregated')],
                            ['entity_pk_value' => 'entity_pk_value', self::AVG_RATING_PERCENT => 'avg(percent_approved)'])
                        ->where('rova.store_id = ' . $storeId)
                        ->group('rova.entity_pk_value')],
                    'product.entity_id = rt.entity_pk_value',
                    [self::AVG_RATING_PERCENT]
                )->group('product.entity_id');
        $productCollection->getSelect()
            ->join(
                ['rt' => $select],
                'e.entity_id = rt.entity_pk_value',
                [self::AVG_RATING_PERCENT => self::AVG_RATING_PERCENT]
            );
        return $productCollection;
    }
    
    public function getRatingFilter($layer)
    {
        if ($this->ratingFilter === null) {
             $this->ratingFilter = $this->objectManager->create(
                \Codazon\AjaxLayeredNavPro\Model\Layer\Filter\Rating::class,
                ['layer' => $layer]
            );
        }
        return $this->ratingFilter;
    }
    
    public function collectionRatingFilter($productCollection, $attributeValue)
    {
        $sqlFieldName = self::AVG_RATING_PERCENT;
        if ($this->getRatingFilterType() == 'interval') {
            $maxPercent = max(0, 100 * $attributeValue / 5);
            $minPercent = max(0, 100 * ($attributeValue - 1) / 5);
            $productCollection->getSelect()->where("({$minPercent} < {$sqlFieldName}) AND ({$sqlFieldName} <= {$maxPercent})");
        } else {
            $minPercent = 100 * $attributeValue / 5;
            $productCollection->getSelect()->where("{$sqlFieldName} >= {$minPercent}");
        }
        return $productCollection;
    }
    
    public function getAvgRatingPercentFieldName()
    {
        return self::AVG_RATING_PERCENT;
    }
    
    public function sortByRating($collection, $direction) {
        $this->attachRatingAvgPercentFieldToCollection($collection);
        $order = self::AVG_RATING_PERCENT;
        $collection->getSelect()->order("$order $direction");
    }
}