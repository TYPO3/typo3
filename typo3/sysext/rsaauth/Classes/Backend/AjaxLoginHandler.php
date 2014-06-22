<?php
namespace TYPO3\CMS\Rsaauth\Backend;

/**
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
	 * @return void
	 */
	public function getRsaPublicKey(array $parameters, \TYPO3\CMS\Core\Http\AjaxRequestHandler $parent) {
		$backend = \TYPO3\CMS\Rsaauth\Backend\BackendFactory::getBackend();
		if ($backend !== NULL) {
			$keyPair = $backend->createNewKeyPair();
			$storage = \TYPO3\CMS\Rsaauth\Storage\StorageFactory::getStorage();
			$storage->put($keyPair->getPrivateKey());
			session_commit();
			$parent->addContent('publicKeyModulus', $keyPair->getPublicKeyModulus());
			$parent->addContent('exponent', sprintf('%x', $keyPair->getExponent()));
			$parent->setContentFormat('json');
		} else {
			$parent->setError('No OpenSSL backend could be obtained for rsaauth.');
		}
	}
}