<?php
/**
 * Smart Send Shipping Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   CodexMedia
 * @package    CodexMedia_SmartSend
 * @author     Codexian
 * @copyright  Copyright (c) 2012 Codex Media
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class CodexMedia_SmartSend_Model_Shipping_Config_Types
{
	public function toOptionArray()
	{
		return array(
			array('value' => 'Carton', 		'label' => Mage::helper( 'adminhtml' )->__( 'Carton' )),
			array('value' => 'Satchel/Bag', 	'label' => Mage::helper( 'adminhtml' )->__( 'Satchel/Bag' )),
			array('value' => 'Tube', 			'label' => Mage::helper( 'adminhtml' )->__( 'Tube' )),
			array('value' => 'Skid', 			'label' => Mage::helper( 'adminhtml' )->__( 'Skid' )),
			array('value' => 'Pallet', 		'label' => Mage::helper( 'adminhtml' )->__( 'Pallet' )),
			array('value' => 'Crate', 			'label' => Mage::helper( 'adminhtml' )->__( 'Crate' )),
			array('value' => 'Flat Pack', 	'label' => Mage::helper( 'adminhtml' )->__( 'Flat Pack' )),
			array('value' => 'Roll', 			'label' => Mage::helper( 'adminhtml' )->__( 'Roll' )),
			array('value' => 'Length', 		'label' => Mage::helper( 'adminhtml' )->__( 'Length' )),
			array('value' => 'Tyre/Wheel',	'label' => Mage::helper( 'adminhtml' )->__( 'Tyre/Wheel' )),
			array('value' => 'Envelope',		'label' => Mage::helper( 'adminhtml' )->__( 'Envelope' ))
		);
	}
}