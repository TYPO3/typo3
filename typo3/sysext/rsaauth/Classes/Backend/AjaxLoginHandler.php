<?php
namespace TYPO3\CMS\Rsaauth\Backend;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Class AjaxLoginHandler
 */
class AjaxLoginHandler {

	/**
	 * Gets RSA Public Key.
	 *
	 * @param array $parameters Parameters (not used)
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $parent The calling parent AJAX object
	 * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. Please use RsaEncryption::getRsaPublicKey as ajax handler instead.
	 */
	public function getRsaPublicKey(array $parameters, \TYPO3\CMS\Core\Http\AjaxRequestHandler $parent) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$rsaEncryptionEncoder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Rsaauth\RsaEncryptionEncoder::class);
		$rsaEncryptionEncoder->getRsaPublicKeyAjaxHandler($parameters, $parent);
	}

}