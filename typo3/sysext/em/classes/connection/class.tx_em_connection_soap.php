<?php
/* **************************************************************
*  Copyright notice
*
*  (c) webservices.nl
*  (c) 2006-2010 Karsten Dambekalns <karsten@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Enter description here...
 *
 */
class tx_em_Connection_Soap {
	/**
	 * valid options passed to the constructor :
	 * wsdl		   : The WSDL location, can be a local file location or
	 *				  an URL.
	 * soapoptions	: Associative array of SOAP options to be passed to
	 *				  the SOAP implementation constructor, only used for
	 *				  the phpsoap implement.
	 * authentication : method of authentication :
	 *				  'headers'   soap headers are used
	 *				  'prefix'	function prefixes are used
	 * prefix		 : optional prefix to be put in front of all methods.
	 * format		 : Which type of return structure :
	 *				  'object'	PHP objects
	 *				  'array'	 PHP arrays, default
	 */
	var $options = array();

	/**
	 * SOAP client, instance of PHP SOAP class
	 *
	 * @var SoapClient
	 */
	var $client = FALSE;

	var $error = FALSE;
	var $username = FALSE;
	var $password = FALSE;
	var $reactid = FALSE;

	/**
	 * Init Soap
	 *
	 * @param	array		$options
	 * @param	string		$username
	 * @param	string		$password
	 * @return	[type]		...
	 */
	function init($options = FALSE, $username = FALSE, $password = FALSE) {
		if ($username !== FALSE) {
			if ($password === FALSE) {
				$this->reactid = $username;
			} else {
				$this->username = $username;
				$this->password = $password;
			}
		}

		$options['format'] = $options['format'] == 'object' ? 'object' : 'array';

		if ($options !== FALSE) {
			$this->options = (array) $options;
		}

		if (defined('SOAP_1_2')) {
			$this->client = new SoapClient($options['wsdl'], (array) $options['soapoptions']);
		} else {
			$this->client = FALSE;
			throw new RuntimeException('PHP soap extension not available', 1333754714);
		}
	}

	/**
	 * Login
	 *
	 * @param	string		$username
	 * @param	string		$password
	 * @return	mixed		FALSE on failure, $reactid on success
	 */
	function login($username, $password) {
		$reactid = $this->call('login', array('username' => $username, 'password' => $password));

		if ($this->error) {
			return FALSE;
		}

		$this->reactid = $reactid;
		$this->username = $username;
		$this->password = FALSE;

		return $reactid;
	}

	/**
	 * Logout
	 *
	 * @return	unknown
	 */
	function logout() {
		$this->call('logout');
		$this->reactid = FALSE;
		if ($this->error) {
			return FALSE;
		}
		return TRUE;
	}


	/**
	 * Soapcall
	 *
	 * @param	unknown_type		$func
	 * @param	unknown_type		$param
	 * @param	unknown_type		$username
	 * @param	unknown_type		$password
	 * @return	unknown
	 */
	function call($func, $param = array(), $username = FALSE, $password = FALSE) {
		if (!$this->client) {
			$this->error = sprintf(
				'Error in %s: No soap client implementation found. ' .
						'Make sure PHP soap extension is available!', __FILE__);
			return FALSE;
		}

		if ($username !== FALSE) {
			if ($password === FALSE) {
				$this->reactid = $username;
			} else {
				$this->username = $username;
				$this->password = $password;
			}
		}

		if ($this->options['authentication'] == 'prefix') {
			$param = array_merge(array('reactid' => $this->reactid), $param);
		}

		if ($this->options['prefix']) {
			$func = $this->options['prefix'] . ucfirst($func);
		}
		$this->error = FALSE;

		return $this->callPhpSOAP($func, $param);
	}

	/**
	 * Call php soap
	 *
	 * @param	unknown_type		$func
	 * @param	unknown_type		$param
	 * @return	unknown
	 */
	function callPhpSOAP($func, $param) {
		$header = NULL;
		if ($this->options['authentication'] == 'headers') {
			if ($this->reactid) {
				$header = new SoapHeader(
					'', 'HeaderAuthenticate',
					(object) array('reactid' => $this->reactid), 1
				);
			} elseif ($this->username && $this->password) {
				$header = new SoapHeader(
					'', 'HeaderLogin',
					(object) array(
						'username' => $this->username,
						'password' => $this->password
					), 1
				);
				$this->password = FALSE;
			}
		}
		 /*return array(
						'username' => $this->username,
						'password' => $this->password,
			 		'func' => $func
					); */

		$result = $this->client->__soapCall($func, $param, NULL, $header);

		if (is_soap_fault($result)) {
			$this->error = $result;
			return FALSE;
		}

		if (is_a($this->client->headersIn['HeaderAuthenticate'], 'stdClass')) {
			$this->reactid = $this->client->headersIn['HeaderAuthenticate']->reactid;
		}

		return $this->options['format'] == 'object' ? $result : $this->object2array($result);
	}

	/**
	 * Convert object to array
	 *
	 * @param	object	$object
	 * @return	array
	 */
	function object2array($object) {
		if (!is_object($object) && !is_array($object)) {
			return $object;
		}

		$array = (array) $object;
		foreach ($array as $key => $value) {
			$array[$key] = $this->object2array($value);
		}
		return $array;
	}

	/**
	 * Convert array to object
	 *
	 * @param	unknown_type		$array
	 * @return	unknown
	 */
	function array2object($array) {
		if (!is_array($array)) {
			return $array;
		}

		foreach ($array as $key => $value) {
			$array[$key] = $this->array2object($value);
		}
		return (object) $array;
	}

	/**
	 * Get last request.
	 *
	 * @return	unknown
	 */
	function lastRequest() {
		return $this->client->__getLastRequest();
	}

	/**
	 * Get last response
	 *
	 * @return	unknown
	 */
	function lastResponse() {
		$this->client->__getLastResponse();
	}

	/**
	 * Get available functions
	 *
	 * @return	unknown
	 */
	function getFunctions() {
		return $this->client->__getFunctions();
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/connection/class.tx_em_connection_soap.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/connection/class.tx_em_connection_soap.php']);
}
?>