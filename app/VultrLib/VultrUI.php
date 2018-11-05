<?php 

namespace vultrui\VultrLib;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
 
class VultrUI {
 
    /**
	 * Vultr API Endpoint
     */

	var $endpoint = "https://api.vultr.com/v1/";

	/**
	 * Either to verify SSL certificate or not (false)
     */

	var $verify_ssl = false;

	/**
	 * Guzzle Client
     */

	private $client = null;

	/**
	 * API Authentication Key
     */

	var $auth_key;

	/**
	 * errors handler
	*/

	public function __construct() {

		$this->auth_key = config('app.vultr_authkey');

		$this->client = new Client([

			'base_uri' => $this->endpoint,

		]);
	}

	public function Request( $method, $resource, $body = true , $headers = [], $params = []){

		/**
		  * Add API Authentication key to headers
		  *
		*/

		$Hdata = [ 'API-Key' => $this->auth_key ];


		/**
		  * Headers
		  *
		*/

		if ( !empty($headers) ) {

			foreach( $headers as $key => $value ) {

				$Hdata[$key] = $value;

			}

		}

		try {

			$resp = $this->client->request($method, $resource,

	    	[
	    		'verify' => $this->verify_ssl,

	    		'headers' => $Hdata,

	    		'form_params' => $params,

	    	]);


			return ( $body === true ) ? json_decode($resp->getBody()->getContents(), true) : $resp;

		} catch( ClientException $e) {
			
			return [ 'error' => $e->getMessage() ];
			
		} catch ( GuzzleException $b) {
			
			return [ 'error' => $b->getMessage() ];

		} catch ( ConnectException $c) {

			return [ 'error' => $c->getMessage() ];
		}

	}

}