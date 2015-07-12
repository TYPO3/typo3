<?php
namespace TYPO3\CMS\Rsaauth;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\AjaxRequestHandler;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * This class adds necessary Javascript code to encrypt fields in a form
 */
class RsaEncryptionEncoder implements SingletonInterface {

	/**
	 * @var bool
	 */
	protected $moduleLoaded = FALSE;

	/**
	 * @var PageRenderer
	 */
	protected $pageRenderer = NULL;

	/**
	 * Load all necessary Javascript files
	 *
	 * @param bool $useRequireJsModule
	 */
	public function enableRsaEncryption($useRequireJsModule = FALSE) {
		if ($this->moduleLoaded || !$this->isAvailable()) {
			return;
		}
		$this->moduleLoaded = TRUE;
		$pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
		// Include necessary javascript files
		if ($useRequireJsModule) {
			$pageRenderer->loadRequireJsModule('TYPO3/CMS/Rsaauth/RsaEncryptionModule');
		} else {
			// Register ajax handler url
			$code = 'var TYPO3RsaEncryptionPublicKeyUrl = ' . GeneralUtility::quoteJSvalue(BackendUtility::getAjaxUrl('RsaEncryption::getRsaPublicKey')) . ';';
			$pageRenderer->addJsInlineCode('TYPO3RsaEncryptionPublicKeyUrl', $code);
			$javascriptPath = ExtensionManagementUtility::siteRelPath('rsaauth') . 'Resources/Public/JavaScript/';
			if (!$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['debug']) {
				$files = array('RsaEncryptionWithLib.min.js');
			} else {
				$files = array(
					'RsaLibrary.js',
					'RsaEncryption.js',
				);
			}
			foreach ($files as $file) {
				$pageRenderer->addJsFile($javascriptPath . $file);
			}
		}
	}

	/**
	 * @return bool
	 */
	public function isAvailable() {
		return trim($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['loginSecurityLevel']) === 'rsa';
	}

	/**
	 * Gets RSA Public Key.
	 *
	 * @return Keypair|NULL
	 */
	public function getRsaPublicKey() {
		$keyPair = NULL;
		$backend = Backend\BackendFactory::getBackend();
		if ($backend !== NULL) {
			$keyPair = $backend->createNewKeyPair();
			$storage = Storage\StorageFactory::getStorage();
			$storage->put($keyPair->getPrivateKey());
			session_commit();
		}

		return $keyPair;
	}

	/**
	 * Ajax handler to return a RSA public key.
	 *
	 * @param array $parameters Parameters (not used)
	 * @param AjaxRequestHandler $parent The calling parent AJAX object
	 */
	public function getRsaPublicKeyAjaxHandler(array $parameters, AjaxRequestHandler $parent) {
		$keyPair = $this->getRsaPublicKey();
		if ($keyPair !== NULL) {
			$parent->addContent('publicKeyModulus', $keyPair->getPublicKeyModulus());
			$parent->addContent('spacer', ':');
			$parent->addContent('exponent', sprintf('%x', $keyPair->getExponent()));
			$parent->setContentFormat('plain');
		} else {
			$parent->setError('No OpenSSL backend could be obtained for rsaauth.');
		}
	}

}
