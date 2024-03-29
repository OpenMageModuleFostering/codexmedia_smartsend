<?php

class CodexMedia_Smartsend_Model_Api extends Mage_Core_Model_Abstract {
	const liveWSDL = 'http://developer.smartsend.com.au/service.asmx?wsdl';
	const testWSDL = 'http://uat.smartservice.smartsend.com.au/service.asmx?wsdl';

	// TTL in days for locations cache
	private $cacheTTL = 1;

	private $locationsCacheFile;

	// Soap connection object
	private $soapClient;

	// Auth details
	private $username = '';
	private $password = '';

	// List of locations
	private $locationList = array();

	// Coming from
	private $postcodeFrom;
	private $suburbFrom;
	private $stateFrom;

	// Going to
	private $postcodeTo;
	private $suburbTo;
	private $stateTo;

	// Arrays of params for quote
	// Each item contains:
	// 	Description: One of -
	// 		Carton, Satchel/Bag, Tube, Skid, Pallet, Crate, Flat Pack, Roll, Length, Tyre/Wheel, Furniture/Bedding, Envelope
	// 	Depth
	// 	Height
	// 	Length
	// 	Weight
	private $quoteItems;

	// Optional promo code
	private $promoCode;

	// Amount to insure
	private $transportAssurance = 0;

	// User type. Options are:
	// EBAY, PROMOTION, VIP
	private $userType = 'VIP';

	// Wether taillift required, if so what and where. Options are:
	// NONE, PICKUP, DELIVERY, BOTH
	private $tailLift = 'NONE';

	// Optional
	private $promotionalCode = '';
	private $onlineSellerId  = '';

	private $receiptedDelivery = 0;

	// Object containing the results of last quote
	private $lastQuoteResults;

	/**
	 * Initialise the Smart Send SOAP API
	 *
	 * @throws Exception
	 * @internal param string $username Smart Send VIP username
	 * @internal param string $password Smart Send VIP password
	 * @internal param bool $useTest Whether to use the test API. Default is false.
	 */

	public function __construct() {
		$this->username = Mage::getStoreConfig( 'carriers/smartsend/ssusername' );
		$this->password = Mage::getStoreConfig( 'carriers/smartsend/sspassword' );

		if ( is_null( $this->username ) && is_null( $this->password ) ) {
			throw new Exception( 'Missing username and password.' );
		}

		$this->ssWSDL = preg_match( '/^test@/i', $this->username ) ? self::testWSDL : self::liveWSDL;
		Mage::log( "SOAP endpoint set to " . $this->ssWSDL );

		$this->locationsCacheFile = dirname( __FILE__ ) . '/locations.data';
	}

	// Only connect to API when needed
	protected function _soapConnect() {
		if ( is_object( $this->soapClient ) ) {
			return;
		}
		$this->soapClient = new SoapClient( $this->ssWSDL );
	}

	public function getQuote() {
		$required = array(
			'postcodeFrom',
			'suburbFrom',
			'stateFrom',
			'postcodeTo',
			'suburbTo',
			'stateTo',
			'userType',
			'tailLift'
		);

		foreach ( $required as $req ) {
			if ( is_null( $this->$req ) ) {
				throw new Exception( "Cannot get quote without '$req' parameter" );
			}
		}

		if ( $this->userType == 'EBAY' && is_null( $this->onlineSellerId ) ) {
			throw new Exception( 'Online Seller ID required for Ebay user type.' );
		}

		if ( $this->userType == 'PROMOTION' && is_null( $this->promotionalCode ) ) {
			throw new Exception( "Promotional code required for user type 'PROMOTION'." );
		}

		$quoteParams['request'] = array(
			'VIPUsername'        => $this->username,
			'VIPPassword'        => $this->password,
			'PostcodeFrom'       => $this->postcodeFrom,
			'SuburbFrom'         => $this->suburbFrom,
			'StateFrom'          => $this->stateFrom,
			'PostcodeTo'         => $this->postcodeTo,
			'SuburbTo'           => $this->suburbTo,
			'StateTo'            => $this->stateTo,
			'UserType'           => $this->userType,
			'OnlineSellerID'     => $this->onlineSellerId,
			'PromotionalCode'    => $this->promotionalCode,
			'ReceiptedDelivery'  => $this->receiptedDelivery,
			'TailLift'           => $this->tailLift,
			'TransportAssurance' => $this->transportAssurance,
			'DeveloperId'        => '929fc786-8199-4b81-af60-029e2bca4f39',
			'Items'              => $this->quoteItems
		);

		Mage::log( print_r( $quoteParams, true ) );

		$this->_soapConnect();
		$this->lastQuoteResults = $this->soapClient->obtainQuote( $quoteParams );

		Mage::log( print_r( $this->lastQuoteResults, true ) );

		return $this->lastQuoteResults;
	}

	/**
	 * @param array $fromDetails Array of 'from' address details: [ postcode, suburb, state, ]
	 */
	public function setFrom( $fromDetails ) {
		list( $this->postcodeFrom, $this->suburbFrom, $this->stateFrom ) = $fromDetails;
	}

	/**
	 * @param array $toDetails Array of 'to' address details: [ postcode, suburb, state, ]
	 */
	public function setTo( $toDetails ) {
		list( $this->postcodeTo, $this->suburbTo, $this->stateTo ) = $toDetails;
	}

	/**
	 * @param $param
	 * @param $value
	 *
	 * @internal param \Set $string optional parameters:
	 *           userType:                        EBAY, CORPORATE, PROMOTION, CASUAL, REFERRAL
	 *           onlineSellerID:            Only if userType = EBAY
	 *           promotionalCode:        Only if userType = PROMOTIONAL
	 *           receiptedDelivery:    Customer signs to indicate receipt of package
	 *           tailLift:                        For heavy items; either a tail lift truck or extra staff
	 *           transportAssurance:    If insurance is required
	 */

	public function setOptional( $param, $value ) {
		$allowed = array(
			'userType'           => array( 'EBAY', 'PROMOTIONAL', 'VIP' ),
			'onlineSellerId'     => '',
			'promotionalCode'    => '',
			'receiptedDelivery'  => array( 1, 0 ),
			'tailLift'           => array( 'NONE', 'PICKUP', 'DELIVERY', 'BOTH' ),
			'transportAssurance' => ''
		);
		if ( !in_array( $param, array_keys( $allowed ) ) ) {
			echo 'Not a settable parameter';

			return;
		}
		if ( is_array( $allowed[ $param ] ) && !in_array( $value, $allowed[ $param ] ) ) {
			echo "'$value' is not a valid value for '$param'";

			return;
		}
		$this->$param = $value;
	}

	/**
	 * Add items to be shipped
	 *
	 * @param array $itemData [ Description, Depth, Height, Length, Weight ]
	 *
	 * @throws Exception
	 */
	public function addItem( array $itemData ) {
		$descriptions = array(
			'Carton',
			'Satchel/Bag',
			'Tube',
			'Skid',
			'Pallet',
			'Crate',
			'Flat Pack',
			'Roll',
			'Length',
			'Tyre/Wheel',
			'Furniture/Bedding',
			'Envelope'
		);
		if ( !in_array( $itemData['Description'], $descriptions ) ) {
			throw new Exception( 'Item must be one of: ' . implode( ', ', $descriptions ) );
		}
		$this->quoteItems[] = $itemData;
	}

	/**
	 * Retrieve official list of locations - postcode, suburb, state
	 *
	 * @param bool $cachedRequested
	 *
	 * @internal param bool $cached true (default) for returning cached data, false for fresh data
	 *
	 * @return array
	 */
	public function getLocations( $cachedRequested = true ) {
		if ( !$cachedRequested ) {
			return $this->_locationsToArray();
		} else {
			if ( file_exists( $this->locationsCacheFile ) ) {
				// Return cached data if not expired
				$fileAge = time() - filemtime( $this->locationsCacheFile );
				if ( $fileAge < $this->cacheTTL * 3600 ) {
					$locationsList = unserialize( file_get_contents( $this->locationsCacheFile ) );
				}
			}
			// Either expired or doesn't exist
			if ( !isset( $locationsList ) ) {
				$locationsList = $this->_locationsToArray();
				file_put_contents( $this->locationsCacheFile, serialize( $locationsList ) );
			}

			return $locationsList;
		}
	}

	// Request locations from SOAP object and convert to an array
	// return: array $this->locationList
	protected function _locationsToArray() {
		$this->_soapConnect();
		$locations = $this->soapClient->GetLocations();
		foreach ( $locations->GetLocationsResult->Location as $location ) {
			$postcode                          = sprintf( "%04d", $location->Postcode );
			$this->locationList[ $postcode ][] = array( $location->Suburb, $location->State );
		}

		return $this->locationList;
	}

	// Get the first town in the list for a postcode
	public function getFirstTown( $postcode ) {
		if ( !preg_match( '/^\d{4}$/', $postcode ) ) {
			return false;
		}
		$locations = $this->getLocations();

		return trim( $locations[ $postcode ][0][0] );
	}

	// Check if town is at postcode
	public function isTownInPostcode( $postcode, $suburb ) {
		$locations = $this->getLocations();
		if ( isset( $locations[ $postcode ] ) ) {
			foreach ( $locations[ $postcode ] as $loc ) {
				if ( strtolower( $suburb ) == strtolower( $loc[0] ) ) {
					return true;
				}
			}

			return false;
		}
	}

	// Return array of suburbs at postcode
	public function getPostcodeSuburbs( $postcode ) {
		$locations = $this->getLocations();
		if ( isset( $locations[ $postcode ] ) ) {
			$towns = array();
			foreach ( $locations[ $postcode ] as $loc ) {
				$towns[] = $loc[0];
			}

			return array_unique( $towns );
		}
	}

	public function getState( $pcode ) {
		$first = (int) $pcode[0]; // First number
		$pcode = (int) $pcode; // Type to integer

		if ( $first == 1 ) {
			return 'NSW';
		}

		if ( $first == 2 ) // ACT or NSW
		{
			if ( ( $pcode >= 2600 && $pcode <= 2618 ) || ( $pcode >= 2900 && $pcode <= 2920 ) ) {
				return 'ACT';
			}

			return 'NSW'; // Defaults to..
		}

		if ( $pcode < 300 ) {
			return 'ACT';
		}

		if ( $first == 3 || $first == 8 ) {
			return 'VIC';
		}

		if ( $first == 4 || $first == 9 ) {
			return 'QLD';
		}

		if ( $first == 5 ) {
			return 'SA';
		}

		if ( $first == 6 ) {
			return 'WA';
		}

		if ( $first == 7 ) {
			return 'TAS';
		}

		// ACT's 0200-0299 already caught
		if ( $first == 0 ) {
			return 'NT';
		}
	}
}