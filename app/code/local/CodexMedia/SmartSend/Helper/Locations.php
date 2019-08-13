<?php


class WL_Auspost_Helper_Location extends Mage_Core_Helper_Abstract
{

	/**
	 *
	 * Gets AUS state code by state name
	 *
	 * @param $name
	 *
	 * @return   string The state code
	 */

	public function getStateCodeByName ($name)
	{
		$states = array(
			'Australian Capital Territory' => 'ACT',
			'New South Wales'              => 'NSW',
			'Northern Territory'           => 'NT',
			'Queensland'                   => 'QLD',
			'South Australia'              => 'SA',
			'Victoria'                     => 'VIC',
			'Western Australia'            => 'WA'
		);
		if (isset($states[$name]))
			return $states[$name];
		return $name;
	}

	/**
	*
	* Gets AUS state name by state code
	* 
	* @param    string $code The state code
	* @return   string The state name
	*/

	public function getStateNameByCode ($code)
	{
		$states = array(
			'ACT' => 'Australian Capital Territory',
			'NSW' => 'New South Wales',
			'NT'  => 'Northern Territory',
			'QLD' => 'Queensland',
			'SA'  => 'South Australia',
			'VIC' => 'Victoria',
			'WA'  => 'Western Australia'
		);
		if (isset($states[$code]))
			return $states[$code];
		return null;
	}
}