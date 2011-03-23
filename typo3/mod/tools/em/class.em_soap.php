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
/* $Id$ */

/**
 * Enter description here...
 *
 */
class em_soap {
	/**
	 * valid options passed to the constructor :
	 * wsdl           : The WSDL location, can be a local file location or
	 *                  an URL.
	 * soapoptions    : Associative array of SOAP options to be passed to
	 *                  the SOAP implementation constructor, only used for
	 *                  the phpsoap implement.
	 * authentication : method of authentication :
	 *                  'headers'   soap headers are used
	 *                  'prefix'    function prefixes are used
	 * prefix         : optional prefix to be put in front of all methods.
	 * implementation : Which type of soap implementation to use :
	 *                  'detect'    automatically detect an implementation.
	 *                  'phpsoap'   PHP builtin SOAP module
	 *                              <http://www.php.net/manual/en/ref.soap.php>
	 *                  'nusoap'    NuSOAP class
	 *                              <http://dietrich.ganx4.com/nusoap>
	 *                  'pearsoap'  PEAR SOAP class
	 *                              <http://pear.php.net/package/SOAP>
	 * format         : Which type of return structure :
	 *                  'object'    PHP objects
	 *                  'array'     PHP arrays, default
	 */
	var $options  = array();

	/**
	 * SOAP client depending on the available implementations, preferably the PHP SOAP class
	 *
	 * @var unknown_type
	 */
	var $client   = false;
	var $error    = false;

	var $username = false;
	var $password = false;
	var $reactid  = false;

	/**
	 * Enter description here...
	 *
	 * @param	array		$options
	 * @param	string		$username
	 * @param	string		$password
	 * @return	[type]		...
	 */
	function init($options=false, $username=false, $password=false) {
		if ($username !== false) {
			if ($password === false) {
				$this->reactid = $username;
			} else {
				$this->username = $username;
				$this->password = $password;
			}
		}

		if (!$options['implementation'] || $options['implementation'] == 'detect') {
				// Avoid autoloading, since it's only a strategy check here:
			if (defined('SOAP_1_2')) {
				$options['implementation'] = 'phpsoap';
			} elseif (class_exists('soapclient', false)) {
				$options['implementation'] = 'nusoap';
			} elseif (class_exists('SOAP_Client', false)) {
				$options['implementation'] = 'pearsoap';
			}
		}

		$options['format'] = $options['format'] == 'object' ? 'object' : 'array';

		if ($options !== false) {
			$this->options = (array)$options;
		}

		switch ($this->options['implementation']) {
			case 'nusoap':
				$this->client = new soapclient($this->options['wsdl'], true);
				$this->client->getProxy();
				break;
			case 'pearsoap':
				$this->client = new SOAP_Client($this->options['wsdl'], true);
				break;
			case 'phpsoap':
				$this->client = new SoapClient($options['wsdl'],(array)$options['soapoptions']);
				break;
			default:
				$this->client = false;
		}

		return ($this->client !== false);
	}

	/**
	 * Enter description here...
	 *
	 * @param	string		$username
	 * @param	string		$password
	 * @return	mixed		false on failure, $reactid on success
	 */
	function login($username, $password) {
		$reactid = $this->call('login', array('username' => $username, 'password' => $password));

		if ($this->error) {
			return false;
		}

		$this->reactid  = $reactid;
		$this->username = $username;
		$this->password = false;

		return $reactid;
	}

	/**
	 * Enter description here...
	 *
	 * @return	unknown
	 */
	function logout() {
		$this->call('logout');
		$this->reactid = false;
		if ($this->error) {
			return false;
		}
		return true;
	}


	/**
	 * Enter description here...
	 *
	 * @param	unknown_type		$func
	 * @param	unknown_type		$param
	 * @param	unknown_type		$username
	 * @param	unknown_type		$password
	 * @return	unknown
	 */
	function call($func, $param=array(), $username=false, $password=false) {
		if (!$this->client) {
			$this->error = "Error in Webservices.class.php: No soap client implementation found. ".
			"Make sure a SOAP library such as 'NuSoap.php' is included.";
			return false;
		}

		if ($username !== false) {
			if ($password === false) {
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
			$func = $this->options['prefix'].ucfirst($func);
		}

		$this->error = false;

		switch ($this->options['implementation']) {
			case 'nusoap'   : return $this->callNuSOAP($func, $param); break;
			case 'pearsoap' : return $this->callPearSOAP($func, $param); break;
			case 'phpsoap'  : return $this->callPhpSOAP($func, $param); break;
		}

		return false;
	}

	/**
	 * Enter description here...
	 *
	 * @param	unknown_type		$func
	 * @param	unknown_type		$param
	 * @return	unknown
	 */
	function callPhpSOAP($func, $param) {
		$header = null;
		if ($this->options['authentication'] == 'headers') {
			if ($this->reactid) {
				$header = new SoapHeader(
				'','HeaderAuthenticate',
				(object)array('reactid' => $this->reactid), 1
				);
			} elseif ($this->username && $this->password) {
				$header = new SoapHeader(
				'','HeaderLogin',
				(object)array(
				'username' => $this->username,
				'password' => $this->password
				), 1
				);
				$this->password = false;
			}
		}

		$result = $this->client->__soapCall($func, $param, NULL, $header);

		if (is_soap_fault($result)) {
			$this->error = $result;
			return false;
		}

		if (is_a($this->client->headersIn['HeaderAuthenticate'],'stdClass')) {
			$this->reactid = $this->client->headersIn['HeaderAuthenticate']->reactid;
		}

		return $this->options['format'] == 'object' ? $result : $this->object2array($result);
	}

	/**
	 * Enter description here...
	 *
	 * @param	unknown_type		$func
	 * @param	unknown_type		$param
	 * @return	unknown
	 */
	function callPearSOAP($func,$param) {
		if ($this->options['authentication'] == 'headers') {
			if ($this->reactid) {
				$this->client->addHeader(
				new SOAP_Header(
				'HeaderAuthenticate', NULL,
				array('reactid' => $this->reactid), 1
				)
				);
			} elseif ($this->username && $this->password) {
				$this->client->addHeader(
				new SOAP_Header(
				'HeaderLogin', NULL,
				array(
				'username' => $this->username,
				'password' => $this->password
				), 1
				)
				);
				$this->password = false;
			}
		}


		$result = $this->client->call($func, $param);

		if (PEAR::isError($result)) {
			$this->error = $result;
			return false;
		}

		if (is_a($this->client->headersIn['HeaderAuthenticate'],'stdClass')) {
			$this->reactid = $this->client->headersIn['HeaderAuthenticate']->reactid;
		}

		return $this->options['format'] == 'object' ? $result : $this->object2array($result);
	}

	/**
	 * Enter description here...
	 *
	 * @param	unknown_type		$func
	 * @param	unknown_type		$param
	 * @return	unknown
	 */
	function callNuSOAP($func,$param) {
		$header = false;
		if ($this->options['authentication'] == 'headers') {
			if ($this->reactid) {
				$header = (
				"<HeaderAuthenticate SOAP-ENV:mustUnderstand='1'>".
				"<reactid>".htmlspecialchars($this->reactid)."</reactid>".
				"</HeaderAuthenticate>"
				);
			} elseif ($this->username && $this->password) {
				$header = (
				"<HeaderLogin SOAP-ENV:mustUnderstand='1'>".
				"<username>".htmlspecialchars($this->username)."</username>".
				"<password>".htmlspecialchars($this->password)."</password>".
				"</HeaderLogin>" //HeaderLogin
				);
				$this->password = false;
			}
		}

		$result = $this->client->call($func, $param, false, false, $header);

		if ($this->error = $this->client->getError()) {
			return false;
		}

		// nusoap header support is very limited
		$headers = $this->client->getHeaders();
		$matches = array();
		if (preg_match('~<([a-z0-9]+:)?reactid[^>]*>([^<]*)</([a-z0-9]+:)?reactid>~is', $headers, $matches)) {
			$this->reactid = $matches[2];
		}

		return $this->options['format'] == 'object' ? $this->array2object($result) : $result;
	}

	/**
	 * Enter description here...
	 *
	 * @param	unknown_type		$object
	 * @return	unknown
	 */
	function object2array($object) {
		if (!is_object($object) && !is_array($object)) {
			return $object;
		}

		$array = (array)$object;
		foreach ($array as $key => $value) {
			$array[$key] = $this->object2array($value);
		}
		return $array;
	}

	/**
	 * Enter description here...
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
		return (object)$array;
	}

	/**
	 * Enter description here...
	 *
	 * @return	unknown
	 */
	function lastRequest() {
		switch ($this->options['implementation']) {
			case 'nusoap'   : return $this->client->request; break;
			case 'pearsoap' : return $this->client->__getlastrequest(); break;
			case 'phpsoap'  : return $this->client->__getLastRequest(); break;
		}

		return false;
	}

	/**
	 * Enter description here...
	 *
	 * @return	unknown
	 */
	function lastResponse() {
		switch ($this->options['implementation']) {
			case 'nusoap'   : return $this->client->response; break;
			case 'pearsoap' : return $this->client->__getlastresponse(); break;
			case 'phpsoap'  : return $this->client->__getLastResponse(); break;
		}

		return false;
	}

	/**
	 * Enter description here...
	 *
	 * @return	unknown
	 */
	function getFunctions() {
		switch ($this->options['implementation']) {
			case 'nusoap'   : return array_keys($this->client->operations); break;
			case 'pearsoap' : return false; break;
			case 'phpsoap'  : return $this->client->__getFunctions(); break;
		}

		return false;
	}
}

?>
