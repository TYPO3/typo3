<?php

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
$parameters = array('eID' => 'dumpFile');
if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('t')) {
	$parameters['t'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('t');
}
if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('f')) {
	$parameters['f'] = (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('f');
}
if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('p')) {
	$parameters['p'] = (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('p');
}

if (\TYPO3\CMS\Core\Utility\GeneralUtility::hmac(implode('|', $parameters), 'resourceStorageDumpFile') === \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('token')) {
	if (isset($parameters['f'])) {
		$file = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFileObject($parameters['f']);
		if ($file->isDeleted() || $file->isMissing()) {
			$file = NULL;
		}
	} else {
		$file = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ProcessedFileRepository')->findByUid($parameters['p']);
		if ($file->isDeleted()) {
			$file = NULL;
		}
	}

	if ($file === NULL) {
		\TYPO3\CMS\Core\Utility\HttpUtility::setResponseCodeAndExit(\TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_404);
	}

	// Hook: allow some other process to do some security/access checks. Hook should issue 403 if access is rejected
	if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['FileDumpEID.php']['checkFileAccess'])) {
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['FileDumpEID.php']['checkFileAccess'] as $classRef) {
			$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
			if (!$hookObject instanceof \TYPO3\CMS\Core\Resource\Hook\FileDumpEIDHookInterface) {
				throw new \UnexpectedValueException('$hookObject must implement interface TYPO3\\CMS\\Core\\Resource\\FileDumpEIDHookInterface', 1394442417);
			}
			$hookObject->checkFileAccess($file);
		}
	}
	$file->getStorage()->dumpFileContents($file);
} else {
	\TYPO3\CMS\Core\Utility\HttpUtility::setResponseCodeAndExit(\TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_403);
}