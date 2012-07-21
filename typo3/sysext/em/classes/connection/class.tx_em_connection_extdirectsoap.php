<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Kamper <steffen@typo3.org>
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

class tx_em_Connection_ExtDirectSoap {
	/**
	 * @var tx_em_Repository
	 */
	var $repository;

	/**
	 * @var tx_em_Connection_Soap
	 */
	var $soap = NULL;


	/**
	 * Keeps instance of settings class.
	 *
	 * @var tx_em_Settings
	 */
	static protected $objSettings;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var array
	 */
	protected $accountData = NULL;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = $this->getSettingsObject()->getSettings();
		/** @var $repository tx_em_Repository */
		$this->repository = t3lib_div::makeInstance('tx_em_Repository', $this->settings['selectedRepository']);

		if (isset($this->settings['fe_u']) && isset($this->settings['fe_p']) && $this->settings['fe_u'] !== '' && $this->settings['fe_p'] !== '' ) {
			$this->setAccountData($this->settings['fe_u'], $this->settings['fe_p']);
		}
	}

	/**
	 * Login test with user credentials
	 *
	 * @deprecated since 4.6, will be removed in 6.0
	 * @return array
	 */
	public function testUserLogin() {

			// There is a theory which states that if ever anybody discovers exactly what this method is for and why it is here,
			// it will instantly disappear and be replaced by something even more bizarre and inexplicable.
			// There is another theory which states that this has already happened.
		t3lib_div::logDeprecatedFunction();

		if (is_array($this->accountData)) {
			$login = FALSE;
			if ($login) {
				$data = array(
					'success' => TRUE,
					'id' => $login
				);
			} else {
				$data = array(
					'success' => FALSE,
					'error' => $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_loginFailed')
				);
			}
		} else {
			$data = array(
				'success' => FALSE,
				'error' => $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_noUserLoginData')
			);
		}

		return $data;
	}

	/**
	 * Show Info of extension record
	 *
	 * @param  array $record
	 * @return string
	 */
	public function showRemoteExtInfo($record) {
		return t3lib_utility_Debug::viewArray(array($record, $this->settings));
	}

	/**
	 * Checks validity of extension key
	 *
	 * @formHandler
	 * @param  array $parameter
	 * @return array
	 */
	public function checkExtensionkey($parameter) {
		$this->initSoap();
		$params = array(
			'extensionKey' => $parameter['extkey']
		);
		$result = $this->soapCall('checkExtensionKey', $params);
		$message = $this->getSoapResultMessageFromCode($result['resultCode']);
		if ($result['resultCode'] == 10501) {
			$return =  array(
				'success' => TRUE,
				'message' => $message,
				'valid' => TRUE,
				'raw' => $result
			);
		} else {
			$return =  array(
				'success' => FALSE,
				'message' => $message,
				'raw' => $result
			);
		}

		return $return;
	}

	/**
	 * Register extension key
	 *
	 * @formHandler
	 * @param  array $parameter
	 * @return array
	 */
	public function registerExtensionkey($parameter) {
		$this->initSoap();
		$params = array(
		 	'registerExtensionKeyData' => array(
				'extensionKey' => $parameter['extkey'],
				'title' => $parameter['title'],
				'description' => $parameter['description']
			)
		);
		$result = $this->soapCall('registerExtensionKey', $params);
		$message = $this->getSoapResultMessageFromCode($result['resultCode']);

		if ($result['resultCode'] == 10503) {
			$return =  array(
				'success' => TRUE,
				'message' => $message,
				'valid' => TRUE,
				'raw' => $result
			);
		} else {
			$return =  array(
				'success' => FALSE,
				'message' => $message,
				'raw' => $result
			);
		}

		return $return;
	}

	/**
	 * Get own extensions
	 *
	 * @return array
	 */
	public function getExtensions() {
		$this->initSoap();
		$params = array(
			'extensionKeyFilterOptions' => array(
				'username' => $this->settings['fe_u']
			)
		);
		$result = $this->soapCall('getExtensionKeys', $params);
		$data = $this->addUploads($result['extensionKeyData']);

		if ($result['simpleResult']['resultCode'] == 10000 && $data !== NULL) {
			$return =  array(
				'success' => TRUE,
				'total' => count($result['extensionKeyData']),
				'data' => $data,
				'raw' => $result
			);
		} else {
			$return =  array(
				'success' => FALSE,
				'raw' => $result
			);
		}

		return $return;
	}

	/**
	 * Delete extension key
	 *
	 * @param  string $key
	 * @return array
	 */
	public function deleteExtensionKey($key) {
		$this->initSoap();
		$params = array(
			'extensionKey' => $key
		);
		$result = $this->soapCall('deleteExtensionKey', $params);
		$message = $this->getSoapResultMessageFromCode($result['resultCode']);

		if ($result['resultCode'] == 10000) {
			$return =  array(
				'success' => TRUE,
				'message' => $this->getSoapResultMessageFromCode(10505), // TER API doesn't send correct result code
				'key' => $key
			);
		} else {
			$return =  array(
				'success' => FALSE,
				'message' => $message,
				'key' => $key
			);
		}

		return $return;
	}

	/**
	 * Transfer extension key to other user
	 *
	 * @param  $key
	 * @param  $user
	 * @return array
	 */
	public function transferExtensionKey($key, $user) {
		$this->initSoap();
		$params = array(
			'modifyExtensionKeyData' => array(
				'extensionKey' => $key,
				'ownerUsername' => $user
			)
		);
		$result = $this->soapCall('modifyExtensionKey', $params);
		$message = $this->getSoapResultMessageFromCode($result['resultCode']);

		if ($result['resultCode'] == 10000) {
			$return =  array(
				'success' => TRUE,
				'message' => $message,
				'key' => $key,
				'user' => $user
			);
		} else {
			$return =  array(
				'success' => FALSE,
				'message' => $message,
				'key' => $key,
				'user' => $user
			);
		}

		return $return;
	}


	/*
	 * protected class functions
	 */

	/**
	 * Sets the account data
	 *
	 * @param  string  $user
	 * @param  string  $password
	 * @return void
	 */
	protected function setAccountData($user, $password) {
		$this->accountData = array(
			'accountData' => array(
				'username' => $user,
				'password' => $password
			)
		);
	}

	/**
	 * Init soap
	 *
	 * @return void
	 */
	protected function initSoap() {
		if(!is_object($this->soap) || !($this->soap instanceof tx_em_Connection_Soap)) {
			if ($this->repository->getWsdlUrl()) {
					/** @var $soap tx_em_Connection_Soap */
				$this->soap = t3lib_div::makeInstance('tx_em_Connection_Soap');
				$this->soap->init(
					array(
						'wsdl' => $this->repository->getWsdlUrl(),
						'soapoptions' => array(
							'trace' => 1,
							'exceptions' => 1
						)
					),
					$this->settings['fe_u'],
					$this->settings['fe_p']
				);
			}
		}
	}

	/**
	 * @param  $data
	 * @return bool|NULL|string|tx_em_Settings|unknown
	 */
	protected function addUploads($data) {
		if (count((array) $data) === 0) {
			return NULL;
		}

		foreach ($data as $key => $extkey) {
			$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'extkey, count(version) as uploads',
				'cache_extensions',
				'extkey=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($extkey['extensionkey'], 'cache_extensions') . ' AND repository=1',
				'extkey'
			);
			$data[$key]['uploads'] = intval($row['uploads']);
			$data[$key]['hasUploads'] = (intval($row['uploads']) > 0);
		}

		return $data;
	}
	/**
	 * Get settings object
	 *
	 * @return tx_em_Settings
	 */
	protected function getSettingsObject() {
		if (!is_object(self::$objSettings) && !(self::$objSettings instanceof tx_em_Settings)) {
			self::$objSettings = t3lib_div::makeInstance('tx_em_Settings');
		}
		return self::$objSettings;
	}

	/**
	 * Executes a soap call
	 *
	 * @param  $name
	 * @param  $params
	 * @return string $response
	 */
	protected function soapCall($name, $params) {
		if (!is_object($this->soap)) {
			return array(
				'success' => FALSE,
				'error' => $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_noUserLoginData')
			);
		}

		try {
			$response = $this->soap->call(
				$name,
				array_merge($this->accountData, $params),
				$this->accountData['accountData']['username'],
				$this->accountData['accountData']['password']
			);
			return $response;
		} catch (SoapFault $error) {
			return array(
				'success' => FALSE,
				'error' => $error->faultstring
			);
		}


	}

	/**
	 * Translates SOAP return codes to messages
	 *
	 * @param  int $code
	 * @return string
	 */
	protected function getSoapResultMessageFromCode($code) {
		switch ($code) {
			case 10000:
				return $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_ok');
			break;
			case 102:
				return $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_userNotExists');
			break;
			case 10500:
				return $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_extkexExists');
			break;
			case 10501:
				return $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_extkexNotExists');
			break;
			case 10502:
				return $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_extkexNotValid');
			break;
			case 10503:
				return $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_extkexRegistered');
			break;
			case 10504:
				return $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_extkexUploadedSuccess');
			break;
			case 10505:
				return $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_extkexDeletedSuccess');
			break;
			default:
				return $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_unknownError');

		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/connection/class.tx_em_connection_extdirectsoap.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/connection/class.tx_em_connection_extdirectsoap.php']);
}

?>