<?php
namespace TYPO3\CMS\Extbase\Mvc\Web;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 * Represents a web request.
 *
 * @api
 */
class Request extends \TYPO3\CMS\Extbase\Mvc\Request {

	/**
	 * @var \TYPO3\CMS\Extbase\Security\Cryptography\HashService
	 */
	protected $hashService;

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
	protected $requestUri;

	/**
	 * @var string The base URI for this request - ie. the host and path leading to the index.php
	 */
	protected $baseUri;

	/**
	 * @var boolean TRUE if the HMAC of this request could be verified, FALSE otherwise
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	protected $hmacVerified = FALSE;

	/**
	 * @var boolean TRUE if the current request is cached, false otherwise.
	 */
	protected $isCached = FALSE;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
	 */
	protected $environmentService;

	/**
	 * @param \TYPO3\CMS\Extbase\Security\Cryptography\HashService $hashService
	 */
	public function injectHashService(\TYPO3\CMS\Extbase\Security\Cryptography\HashService $hashService) {
		$this->hashService = $hashService;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
	 *
	 * @return void
	 */
	public function injectEnvironmentService(\TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService) {
		$this->environmentService = $environmentService;
	}

	/**
	 * Sets the request method
	 *
	 * @param string $method Name of the request method
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidRequestMethodException if the request method is not supported
	 */
	public function setMethod($method) {
		if ($method === '' || strtoupper($method) !== $method) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidRequestMethodException('The request method "' . $method . '" is not supported.', 1217778382);
		}
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
	 * @param string $requestUri URI of this web request
	 * @return void
	 */
	public function setRequestUri($requestUri) {
		$this->requestUri = $requestUri;
	}

	/**
	 * Returns the request URI
	 *
	 * @return string URI of this web request
	 * @api
	 */
	public function getRequestUri() {
		return $this->requestUri;
	}

	/**
	 * Sets the base URI for this request.
	 *
	 * @param string $baseUri New base URI
	 * @return void
	 */
	public function setBaseUri($baseUri) {
		$this->baseUri = $baseUri;
	}

	/**
	 * Returns the base URI
	 *
	 * @return string Base URI of this web request
	 * @api
	 */
	public function getBaseUri() {
		if ($this->environmentService->isEnvironmentInBackendMode()) {
			return $this->baseUri . TYPO3_mainDir;
		} else {
			return $this->baseUri;
		}
	}

	/**
	 * Could the request be verified via a HMAC?
	 *
	 * @param boolean $hmacVerified TRUE if request could be verified, FALSE otherwise
	 * @return void
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	public function setHmacVerified($hmacVerified) {
		$this->hmacVerified = (boolean) $hmacVerified;
	}

	/**
	 * Could the request be verified via a HMAC?
	 *
	 * @return boolean TRUE if request could be verified, FALSE otherwise
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	public function isHmacVerified() {
		return $this->hmacVerified;
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
			$referringRequest = new \TYPO3\CMS\Extbase\Mvc\Web\Request();
			$arguments = array();
			if (isset($referrerArray['arguments'])) {
				$serializedArgumentsWithHmac = $referrerArray['arguments'];
				$serializedArguments = $this->hashService->validateAndStripHmac($serializedArgumentsWithHmac);
				$arguments = unserialize(base64_decode($serializedArguments));
				unset($referrerArray['arguments']);
			}
			$referringRequest->setArguments(\TYPO3\CMS\Extbase\Utility\ArrayUtility::arrayMergeRecursiveOverrule($arguments, $referrerArray));
			return $referringRequest;
		}
		return NULL;
	}
}

?>