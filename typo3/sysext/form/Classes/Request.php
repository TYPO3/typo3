<?php
namespace TYPO3\CMS\Form;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Patrick Broens (patrick@patrickbroens.nl)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Request Handler for Form
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class Request implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Prefix for the name attributes
	 * of the superglobals $_POST and $_GET.
	 *
	 * @var string
	 */
	protected $prefix = 'tx_form';

	/**
	 * Method used for submitting data
	 * Can be "get" or "post"
	 *
	 * @var string
	 */
	protected $method = 'post';

	/**
	 * Session data
	 *
	 * Will only be stored when the form has been submitted successfully
	 *
	 * @var array
	 */
	protected $sessionData = array();

	/**
	 * Set the prefix used in the form
	 * If prefix is available in configuration array of the object, it will take it from there.
	 * Otherwise if not given at all, it will set it to default
	 *
	 * @param string $prefix Value containing characters a-z, A-Z, 0-9, _ and -
	 * @return void
	 */
	public function setPrefix($prefix = 'tx_form') {
		if (empty($prefix)) {
			$prefix = 'tx_form';
		}
		$prefix = preg_replace('/\\s/', '_', (string) $prefix);
		$this->prefix = preg_replace('/[^a-zA-Z0-9_\\-]/', '', $prefix);
	}

	/**
	 * Get the prefix
	 *
	 * @return string
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * Set the method used for submitting the data
	 * When used right it will only allow data send by the right method
	 *
	 * @param string $method The method
	 * @return void
	 */
	public function setMethod($method = 'get') {
		$allowedMethods = array(
			'post',
			'get',
			'session'
		);
		$method = strtolower((string) $method);
		if ($GLOBALS['TSFE']->loginUser) {
			$this->sessionData = $GLOBALS['TSFE']->fe_user->getKey('user', $this->prefix);
		} else {
			$this->sessionData = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->prefix);
		}
		if (!empty($this->sessionData)) {
			$method = 'session';
		}
		if (!in_array($method, $allowedMethods)) {
			$method = 'post';
		}
		$this->method = $method;
	}

	/**
	 * Returns the method of this request handler
	 *
	 * @return string
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Access values contained in the superglobals as public members
	 * POST and GET are filtered by prefix of the form
	 * Order of precedence: 1. GET, 2. POST, 3. COOKIE, 4. SERVER, 5. ENV
	 *
	 * @param string $key Parameter to look for
	 * @return mixed
	 */
	public function get($key) {
		switch (TRUE) {
			case $this->method === 'get' && isset($_GET[$this->prefix][$key]):
				return $_GET[$this->prefix][$key];
			case $this->method === 'post' && isset($_POST[$this->prefix][$key]):
				return $_POST[$this->prefix][$key];
			case $this->method === 'session' && isset($this->sessionData[$key]):
				return $this->sessionData[$key];
			default:
				return NULL;
		}
	}

	/**
	 * Check to see if a property is set
	 *
	 * @param string $key Parameter to look for
	 * @return boolean
	 */
	public function has($key) {
		switch (TRUE) {
			case $this->method === 'get' && isset($_GET[$this->prefix][$key]):
				return TRUE;
			case $this->method === 'post' && isset($_POST[$this->prefix][$key]):
				return TRUE;
			case $this->method === 'session' && isset($this->sessionData[$key]):
				return TRUE;
			default:
				return FALSE;
		}
	}

	/**
	 * Check to see if there is a request
	 *
	 * @return boolean
	 */
	public function hasRequest() {
		switch (TRUE) {
			case $this->method === 'get' && isset($_GET[$this->prefix]):
				return TRUE;
			case $this->method === 'post' && isset($_POST[$this->prefix]):
				return TRUE;
			case $this->method === 'session' && !empty($this->sessionData):
				return TRUE;
			default:
				return FALSE;
		}
	}

	/**
	 * Retrieve a member of the $_GET superglobal within the prefix
	 *
	 * If no $key is passed, returns the entire $_GET array within the prefix.
	 *
	 * @param string $key Parameter to search for
	 * @param mixed $default Default value to use if key not found
	 * @return mixed Returns NULL if key does not exist
	 */
	public function getQuery($key = NULL, $default = NULL) {
		if ($key === NULL) {
			return $_GET[$this->prefix];
		}
		return isset($_GET[$this->prefix][$key]) ? $_GET[$this->prefix][$key] : $default;
	}

	/**
	 * Retrieve a member of the $_POST superglobal within the prefix
	 *
	 * If no $key is passed, returns the entire $_POST array within the prefix.
	 *
	 * @param string $key Parameter to search for
	 * @param mixed $default Default value to use if key not found
	 * @return mixed Returns NULL if key does not exist
	 */
	public function getPost($key = NULL, $default = NULL) {
		if ($key === NULL) {
			return $_POST[$this->prefix];
		}
		return isset($_POST[$this->prefix][$key]) ? $_POST[$this->prefix][$key] : $default;
	}

	/**
	 * Retrieve a member of the $sessionData variable
	 *
	 * If no $key is passed, returns the entire $sessionData array
	 *
	 * @param string $key Parameter to search for
	 * @param mixed $default Default value to use if key not found
	 * @return mixed Returns NULL if key does not exist
	 */
	public function getSession($key = NULL, $default = NULL) {
		if ($key === NULL) {
			return $this->sessionData;
		}
		return isset($this->sessionData[$key]) ? $this->sessionData[$key] : $default;
	}

	/**
	 * Retrieve a member of the $_POST or $_GET superglobals or session data
	 * according to the used method.
	 *
	 * If no $key is passed, it returns the entire method array within the prefix.
	 *
	 * @param string $key The member name
	 * @param string $default Default value if there is no $_POST, $_GET or session variable
	 * @return mixed The member, or FALSE when wrong method is used
	 */
	public function getByMethod($key = NULL, $default = NULL) {
		if ($this->method === 'get') {
			return $this->getQuery($key, $default);
		} elseif ($this->method === 'post') {
			return $this->getPost($key, $default);
		} elseif ($this->method === 'session') {
			return $this->getSession($key, $default);
		} else {
			return FALSE;
		}
	}

	/**
	 * Store the form input in a session
	 *
	 * @return void
	 */
	public function storeSession() {
		if ($GLOBALS['TSFE']->loginUser) {
			$GLOBALS['TSFE']->fe_user->setKey('user', $this->prefix, $this->getByMethod());
		} else {
			$GLOBALS['TSFE']->fe_user->setKey('ses', $this->prefix, $this->getByMethod());
		}
		$GLOBALS['TSFE']->storeSessionData();
	}

	/**
	 * Destroy the session data for the form
	 *
	 * @return void
	 */
	public function destroySession() {
		$this->removeFiles();
		if ($GLOBALS['TSFE']->loginUser) {
			$GLOBALS['TSFE']->fe_user->setKey('user', $this->prefix, NULL);
		} else {
			$GLOBALS['TSFE']->fe_user->setKey('ses', $this->prefix, NULL);
		}
		$GLOBALS['TSFE']->storeSessionData();
	}

	/**
	 * Store uploaded files in the typo3temp and return the information of those
	 * files
	 *
	 * @return void
	 */
	public function storeFiles() {
		$formData = $this->getByMethod();
		if (isset($_FILES[$this->prefix]) && is_array($_FILES[$this->prefix])) {
			foreach ($_FILES[$this->prefix]['tmp_name'] as $fieldName => $uploadedFile) {
				if (is_uploaded_file($uploadedFile)) {
					$tempFilename = \TYPO3\CMS\Core\Utility\GeneralUtility::upload_to_tempfile($uploadedFile);
					if (TYPO3_OS === 'WIN') {
						$tempFilename = \TYPO3\CMS\Core\Utility\GeneralUtility::fixWindowsFilePath($tempFilename);
					}
					if ($tempFilename !== '') {
						// Use finfo to get the mime type
						$finfo = finfo_open(FILEINFO_MIME_TYPE);
						$mimeType = finfo_file($finfo, $tempFilename);
						finfo_close($finfo);
						$formData[$fieldName] = array(
							'tempFilename' => $tempFilename,
							'originalFilename' => $_FILES[$this->prefix]['name'][$fieldName],
							'type' => $mimeType,
							'size' => (int) $_FILES[$this->prefix]['size'][$fieldName]
						);
					}
				}
			}
		}
		switch ($this->getMethod()) {
			case 'post':
				$_POST[$this->prefix] = $formData;
				break;
			case 'get':
				$_GET[$this->prefix] = $formData;
				break;
			case 'session':
				$this->sessionData = $formData;
				break;
		}
	}

	/**
	 * Remove uploaded files from the typo3temp
	 *
	 * @return void
	 */
	protected function removeFiles() {
		$values = $this->getByMethod();
		if (is_array($values)) {
			foreach ($values as $value) {
				if (is_array($value) && isset($value['tempFilename'])) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile($value['tempFilename']);
				}
			}
		}
	}

}

?>