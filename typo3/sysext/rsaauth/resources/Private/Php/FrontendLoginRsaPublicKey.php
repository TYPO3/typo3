<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

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

/** @var \TYPO3\CMS\Rsaauth\Backend\AbstractBackend $backend */
$backend = \TYPO3\CMS\Rsaauth\Backend\BackendFactory::getBackend();
if ($backend !== NULL) {
	$keyPair = $backend->createNewKeyPair();
	$storage = \TYPO3\CMS\Rsaauth\Storage\StorageFactory::getStorage();
	$storage->put($keyPair->getPrivateKey());
	session_commit();

	echo $keyPair->getPublicKeyModulus() . ':' . sprintf('%x', $keyPair->getExponent()) . ':';
}
