<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Dmitry Dulepov <dmitry@typo3.org>
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
 * This class contain an RSA keypair class. Its purpose is to keep to keys
 * and trasnfer these keys between other PHP classes.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_rsaauth
 */
final class tx_rsaauth_keypair {

	/**
	 * RSA public exponent (3 or 0x10001)
	 *
	 * @var	int
	 */
	protected	$exponent = 0x10001;

	/**
	 * The private key
	 *
	 * @var	string
	 */
	protected	$privateKey = '';

	/**
	 * The public key modulus
	 *
	 * @var	string
	 */
	protected	$publicKeyModulus = '';

	/**
	 * Retrieves the exponent.
	 *
	 * @return	string	The exponent
	 */
	public function getExponent() {
		return $this->exponent;
	}

	/**
	 * Sets the private key
	 *
	 * @param	string	$privateKey	The new private key
	 * @return	void
	 */
	public function setExponent($exponent) {
		$this->exponent = $exponent;
	}

	/**
	 * Retrieves the private key.
	 *
	 * @return	string	The private key
	 */
	public function getPrivateKey() {
		return $this->privateKey;
	}

	/**
	 * Sets the private key
	 *
	 * @param	string	$privateKey	The new private key
	 * @return	void
	 */
	public function setPrivateKey($privateKey) {
		$this->privateKey = $privateKey;
	}

	/**
	 * Retrieves the public key modulus
	 *
	 * @return	string	The public key modulus
	 */
	public function getPublicKeyModulus() {
		return $this->publicKeyModulus;
	}

	/**
	 * Sets the public key modulus
	 *
	 * @param	string	$publicKeyModulus	The new public key modulus
	 * @return	void
	 */
	public function setPublicKey($publicKeyModulus) {
		$this->publicKeyModulus = $publicKeyModulus;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/sv1/backends/class.tx_rsaauth_keypair.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rsaauth/sv1/backends/class.tx_rsaauth_keypair.php']);
}

?>