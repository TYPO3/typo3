<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Represents a web request.
 *
 * @package Extbase
 * @subpackage MVC\Web
 * @version $ID:$
 *
 * @scope prototype
 * @api
 */
class Tx_Extbase_MVC_Web_Request extends Tx_Extbase_MVC_Request {

	/**
	 * @var string The requested representation format
	 */
	protected $format = 'html';

	/**
	 * @var string Contains the request method
	 */
	protected $method = 'GET';

	/**
	 * @var string
	 */
	protected $requestURI;

	/**
	 * @var string The base URI for this request - ie. the host and path leading to the index.php
	 */
	protected $baseURI;

	/**
	 * @var boolean TRUE if the HMAC of this request could be verified, FALSE otherwise
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 1.6.0
	 */
	protected $hmacVerified = FALSE;

	/**
	 * @var boolean TRUE if the current request is cached, false otherwise.
	 */
	protected $isCached = FALSE;

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Sets the request method
	 *
	 * @param string $method Name of the request method
	 * @return void
	 * @throws Tx_Extbase_MVC_Exception_InvalidRequestMethod if the request method is not supported
	 */
	public function setMethod($method) {
		if ($method === '' || (strtoupper($method) !== $method)) throw new Tx_Extbase_MVC_Exception_InvalidRequestMethod('The request method "' . $method . '" is not supported.', 1217778382);
		$this->method = $method;
	}

	/**
	 * Returns the name of the request method
	 *
	 * @return string Name of the request method
	 * @api
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Sets the request URI
	 *
	 * @param string $requestURI URI of this web request
	 * @return void
	 */
	public function setRequestURI($requestURI) {
		$this->requestURI = $requestURI;
	}

	/**
	 * Returns the request URI
	 *
	 * @return string URI of this web request
	 * @api
	 */
	public function getRequestURI() {
		return $this->requestURI;
	}

	/**
	 * Sets the base URI for this request.
	 *
	 * @param string $baseURI New base URI
	 * @return void
	 */
	public function setBaseURI($baseURI) {
		$this->baseURI = $baseURI;
	}

	/**
	 * Returns the base URI
	 *
	 * @return string Base URI of this web request
	 * @api
	 */
	public function getBaseURI() {
		if (TYPO3_MODE === 'BE') {
			return $this->baseURI . TYPO3_mainDir;
		} else {
			return $this->baseURI;
		}
	}

	/**
	 * Could the request be verified via a HMAC?
	 *
	 * @param boolean $hmacVerified TRUE if request could be verified, FALSE otherwise
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 1.6.0
	 */
	public function setHmacVerified($hmacVerified) {
		$this->hmacVerified = (boolean)$hmacVerified;
	}

	/**
	 * Could the request be verified via a HMAC?
	 *
	 * @return boolean TRUE if request could be verified, FALSE otherwise
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 1.6.0
	 */
	public function isHmacVerified() {
		return $this->hmacVerified;
	}

	/**
	 * Returns the data array of the current content object
	 *
	 * @return array data of the current cObj
	 * @deprecated since Extbase 1.3.0; will be removed in Extbase 1.5.0. Use the ConfigurationManager to retrieve the current ContentObject
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getContentObjectData() {
		t3lib_div::logDeprecatedFunction();
		$contentObject = $this->configurationManager->getContentObject();
		return $contentObject->data;
	}

	/**
	 * Set if the current request is cached.
	 *
	 * @param boolean $isCached
	 */
	public function setIsCached($isCached) {
		$this->isCached = (boolean) $isCached;
	}
	/**
	 * Return whether the current request is a cached request or not.
	 *
	 * @api (v4 only)
	 * @return boolean the caching status.
	 */
	public function isCached() {
		return $this->isCached;
	}

	/**
	 * Get a freshly built request object pointing to the Referrer.
	 *
	 * @return Request the referring request, or NULL if no referrer found
	 */
	public function getReferringRequest() {
		if (isset($this->internalArguments['__referrer']) && is_array($this->internalArguments['__referrer'])) {
			$referrerArray = $this->internalArguments['__referrer'];

			$referringRequest = new Tx_Extbase_MVC_Web_Request;

			$arguments = array();
			if (isset($referrerArray['arguments'])) {
				$arguments = unserialize($referrerArray['arguments']);
				unset($referrerArray['arguments']);
			}

			$referringRequest->setArguments(Tx_Extbase_Utility_Arrays::arrayMergeRecursiveOverrule($arguments, $referrerArray));
			return $referringRequest;
		}
		return NULL;
	}
}
?>