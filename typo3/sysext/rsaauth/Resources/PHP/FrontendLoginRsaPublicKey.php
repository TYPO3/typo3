<?php
defined('TYPO3_MODE') or die();

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

\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
	'The generation of the RSA public key was moved to the ajax handler \'RsaEncryptionEncoder::getRsaPublicKey\'. Please use the rsaauth api to encrypt your form fields. This script will be removed in TYPO3 CMS 8.'
);

/** @var \TYPO3\CMS\Rsaauth\Backend\AbstractBackend $backend */
$backend = \TYPO3\CMS\Rsaauth\Backend\BackendFactory::getBackend();
if ($backend !== NULL) {
	$keyPair = $backend->createNewKeyPair();
	$storage = \TYPO3\CMS\Rsaauth\Storage\StorageFactory::getStorage();
	$storage->put($keyPair->getPrivateKey());
	session_commit();

	echo $keyPair->getPublicKeyModulus() . ':' . sprintf('%x', $keyPair->getExponent()) . ':';
}
