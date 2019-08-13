<?php

class CodexMedia_SmartSend_Model_Shipping_Carrier_SmartSend
	extends Mage_Shipping_Model_Carrier_Abstract
	implements Mage_Shipping_Model_Carrier_Interface
{
	protected $_code = 'smartsend';

	// API auth details
	private $username = '';
	private $password = '';

	// Coming from
	private $postcodeFrom;
	private $suburbFrom;
	private $stateFrom;

	// Going to
	private $postcodeTo;
	private $suburbTo;
	private $stateTo;

	private $tailLiftFromFlag;
	private $tailLiftToFlag;

	private $itemList = array();

	/**
	* Return calculated shipping rates
	*
	* @param Mage_Shipping_Model_Rate_Request $request
	* @return Mage_Shipping_Model_Rate_Result
	*/
	public function collectRates( Mage_Shipping_Model_Rate_Request $request )
	{

		if( !$this->getConfigFlag( 'active' ) )
			return false;
		
		$fromCountry = Mage::getStoreConfig( 'shipping/origin/country_id' );

		if( $request->getDestCountryId() ) $toCountry = $request->getDestCountryId();
		else $toCountry = 'AU';
		
		// Smart Send only available to/from Australia (at the moment)
		if( 'AU' != $fromCountry || 'AU' != $toCountry )
			return false;

		// Must have username/password
		if( !$this->getConfigData('ssusername') || !$this->getConfigData('sspassword') )
			return false;


		$cartItems = Mage::getModel( 'checkout/session' )->getQuote()->getAllItems();
		$totalItems = Mage::getModel('checkout/cart')->getQuote()->getItemsCount();

		$_type = $this->getConfigData('type');

		$result = Mage::getModel('shipping/rate_result');
		$api = Mage::getModel('smartsend/api');

		// To
		$this->postcodeTo = $request->getDestPostcode();
		$this->suburbTo = $request->getDestCity();
		$this->stateTo = $api->getState($this->postcodeTo);

		// Check is valid town at postcode
		if ( !$api->isTownInPostcode( $this->postcodeTo, $this->suburbTo ) )
		{
			$pcTowns = array();
			foreach( $api->getPostcodeSuburbs($this->postcodeTo) as $t )
				$pcTowns[] = ucwords(strtolower($t));
			$errString = 'Error in shipping calculation: You gave your town as ' . $this->suburbTo . ' for postcode '.$this->postcodeTo.'. Towns at that postcode include: '.
					implode( ', ', $pcTowns ).". Return to the 'Shipping Information' tab and enter one of these suburbs.";

			Mage::log(  "Error: ".$this->suburbTo.' is not in the town list at '.$this->postcodeTo );
			$method = $this->_makeMethod( 'Error in shipping calculation', 10 );
			$result->append( $this->_resultError($errString) );
			return $result;
		}

		// Free shipping postcodes - replace multiple spaces and newlines with one space
		$freePostcodes = preg_replace( '/[\s\n]{2,}/m', ' ', $this->getConfigData('freepostcodes'));
		if (!empty($freePostcodes))
		{
			if ( in_array( $this->postcodeTo, explode( ' ', $freePostcodes ) ) )
			{
				// Get it free
				$description = 'Free Shipping to '.$this->suburbTo.' ('.$this->postcodeTo.')';
				$method = $this->_makeMethod( $description, 0 );
				$result->append( $method );
				return $result;
			}
		}

		// From
		$this->postcodeFrom = Mage::getStoreConfig('shipping/origin/postcode');
		$this->suburbFrom = Mage::getStoreConfig('shipping/origin/city');
		$this->stateFrom = $api->getState($this->postcodeFrom);
		
		$api->setFrom( array($this->postcodeFrom, $this->suburbFrom, $this->stateFrom) );
		$api->setTo( array($this->postcodeTo, $this->suburbTo, $this->stateTo) );

		$cartTotal = 0;
		$pickupFlag = $deliveryFlag = false;

		if( $request->getAllItems() )
		{
			$items =  Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems();
			foreach( $items as $item )
			{
				$product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
				$wgt = (int)$product->getWeight();
				$qty = $item->getQty();
				$cartTotal += $item->getPrice();

				foreach( range( 1, $qty ) as $blah )
					$this->itemList[] = array(
						'Description'	=> $_type,
						'Weight' 		=> ceil($wgt),
						'Depth' 			=> ceil($product->getDepth()),
						'Length' 		=> ceil($product->getWidth()),
						'Height' 		=> ceil($product->getHeight())
						);

				if( $wgt >= 30 )
				{
					$tlFrom = $this->getConfigData('tailliftfrom');
					if( $wgt >= $tlFrom && $tlFrom >= 30 )
						$pickupFlag = true;
					if( $wgt >= $this->getConfigData('tailliftto') )
						$deliveryFlag = true;
				}
			}
		}

		if( count( $this->itemList ) )
		{

			foreach( $this->itemList as $item )
				$api->addItem( $item );

			// Transport Assurance
			if ( $this->getConfigData('assurance') )
			{
				$api->setOptional( 'transportAssurance', $cartTotal );
			}

			// Receipted delivery?
			if( $this->getConfigData('receipted') )
				$api->setOptional( 'receiptedDelivery', 'true' );

			// Tail-lift options
			$tailLift = 'NONE'; // Default
			if( $pickupFlag ) $tailLift = 'PICKUP';
			if( $deliveryFlag )
			{
				if( $pickupFlag ) $tailLift = 'BOTH';
				else $tailLift = 'DELIVERY';
			}
			$api->setOptional( 'tailLift', $tailLift );

			$quoteResult = $api->getQuote()->ObtainQuoteResult;

			// Zero means success
			if( $quoteResult->StatusCode != 0 )
			{
				if( $quoteResult->StatusCode == -1 )
				{
					Mage::log( print_r( $quoteResult, true ) );
					$result->append( $this->_resultError( 'ERROR: ' . (string)$quoteResult->StatusMessages->string ));
					return $result;
				}
				return;
			}

			$quote = $quoteResult->Quotes->Quote;

			if( is_object( $quote ) ) $quotes = array( $quote ); // Convert to array if single object
			else $quotes = $quote;

			foreach( $quotes as $quote )
			{
				$description = $quote->TransitDescription;
				if( $quote->SameDayPickupAvailable ) $description .=' (same-day pickup available before ' . $quote->SameDayPickupCutOffTime . ') ';
				$method = $this->_makeMethod( $description, $quote->TotalPrice );
				$result->append( $method );
			}

			return $result;
		}
	}

	function _resultError($str)
	{
		$error = Mage::getModel("shipping/rate_result_error");
      $error->setCarrier($this->_code);
      $error->setErrorMessage($str);
      return $error;
	}


	/**
	 * Create the shipping method object for appending to returned result
	 *
	 * @return obj $method
	 */
	protected function _makeMethod( $description, $cost )
	{
		$method = Mage::getModel('shipping/rate_result_method');

		$method->setCarrier($this->_code);
		$method->setCarrierTitle( $this->getConfigData('title') ); 

		$method->setMethod('smartsend');
		$method->setMethodTitle($description);

		$method->setPrice($this->_calcHandling($cost));
		$method->setCost($cost);

		return $method;
	}

	protected function _calcHandling( $price )
	{
		$fee = $this->getConfigData('handling_fee');
		if( $fee )
		{
			$type = $this->getConfigData('handling_type');
			if( $type == 'F' )
				$price = $price + $fee;
			else if( $type == 'P' )
				$price = $price + $price * $fee / 100;
		}
		return $price;
	}

	public function getAllowedMethods()
	{
		return array( $this->_code => $this->getConfigData('name') );
	}
}
