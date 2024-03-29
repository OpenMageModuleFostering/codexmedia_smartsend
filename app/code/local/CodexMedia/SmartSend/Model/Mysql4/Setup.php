<?php

class CodexMedia_SmartSend_Model_Mysql4_Setup extends Mage_Eav_Model_Entity_Setup
{
	public function getDefaultEntities()
    {
    	return array(
            'catalog_product' => array(
                'entity_model'      => 'catalog/product',
                'attribute_model'   => 'catalog/resource_eav_attribute',
                'table'             => 'catalog/product',
                'additional_attribute_table' => 'catalog/eav_attribute',
                'entity_attribute_collection' => 'catalog/product_attribute_collection',
                'attributes'        => array(
                		'width' => array(
                				'group'             => 'General',
                				'type'              => 'decimal',
                				'backend'           => '',
                				'frontend'          => '',
                				'label'             => 'Width',
                				'input'             => 'text',
                				'class'             => '',
                				'source'            => '',
                				'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
                				'visible'           => true,
                				'required'          => true,
                				'user_defined'      => false,
                				'default'           => '',
                				'searchable'        => false,
                				'filterable'        => false,
                				'comparable'        => false,
                				'visible_on_front'  => false,
                				'visible_in_advanced_search' => false,
                				'used_in_product_listing' => false,
                				'used_for_sort_by'  => false,
                				'unique'            => false,
                		),
                		'height' => array(
                				'group'             => 'General',
                				'type'              => 'decimal',
                				'backend'           => '',
                				'frontend'          => '',
                				'label'             => 'Height',
                				'input'             => 'text',
                				'class'             => '',
                				'source'            => '',
                				'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
                				'visible'           => true,
                				'required'          => true,
                				'user_defined'      => false,
                				'default'           => '',
                				'searchable'        => false,
                				'filterable'        => false,
                				'comparable'        => false,
                				'visible_on_front'  => false,
                				'visible_in_advanced_search' => false,
                				'used_in_product_listing' => false,
                				'used_for_sort_by'  => false,
                				'unique'            => false,
                		),
                		'depth' => array(
                				'group'             => 'General',
                				'type'              => 'decimal',
                				'backend'           => '',
                				'frontend'          => '',
                				'label'             => 'Depth',
                				'input'             => 'text',
                				'class'             => '',
                				'source'            => '',
                				'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
                				'visible'           => true,
                				'required'          => true,
                				'user_defined'      => false,
                				'default'           => '',
                				'searchable'        => false,
                				'filterable'        => false,
                				'comparable'        => false,
                				'visible_on_front'  => false,
                				'visible_in_advanced_search' => false,
                				'used_in_product_listing' => false,
                				'used_for_sort_by'  => false,
                				'unique'            => false,
                		),
                )
            )
        );
    }
}