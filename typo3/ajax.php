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

/**
 * AJAX dispatcher
 *
 * @author Benjamin Mack <mack@xnos.org>
 */

$TYPO3_AJAX = TRUE;

// This is a list of requests that don't necessarily need a valid BE user
$noUserAjaxIDs = array(
	'BackendLogin::login',
	'BackendLogin::logout',
	'BackendLogin::refreshLogin',
	'BackendLogin::isTimedOut',
	'BackendLogin::getChallenge',
	'BackendLogin::getRsaPublicKey',
);

// First get the ajaxID
$ajaxID = isset($_POST['ajaxID']) ? $_POST['ajaxID'] : $_GET['ajaxID'];
if (isset($ajaxID)) {
	$ajaxID = (string)stripslashes($ajaxID);
}

// If we're trying to do an ajax login, don't require a user.
if (in_array($ajaxID, $noUserAjaxIDs)) {
	define('TYPO3_PROCEED_IF_NO_USER', 2);
}

require __DIR__ . '/init.php';

// Finding the script path from the registry
$ajaxRegistryEntry = isset($GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX'][$ajaxID]) ? $GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX'][$ajaxID] : NULL;
$ajaxScript = NULL;
$csrfTokenCheck = FALSE;
if ($ajaxRegistryEntry !== NULL) {
	if (is_array($ajaxRegistryEntry)) {
		if (isset($ajaxRegistryEntry['callbackMethod'])) {
			$ajaxScript = $ajaxRegistryEntry['callbackMethod'];
			$csrfTokenCheck = $ajaxRegistryEntry['csrfTokenCheck'];
		}
	} else {
		// @Deprecated since 6.2 will be removed two versions later
		$ajaxScript = $ajaxRegistryEntry;
	}
}

// Instantiating the AJAX object
$ajaxObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Http\\AjaxRequestHandler', $ajaxID);
$ajaxParams = array();

// Evaluating the arguments and calling the AJAX method/function
if (empty($ajaxID)) {
	$ajaxObj->setError('No valid ajaxID parameter given.');
} elseif (empty($ajaxScript)) {
	$ajaxObj->setError('No backend function registered for ajaxID "' . $ajaxID . '".');
} else {
	$success = TRUE;
	$tokenIsValid = TRUE;
	if ($csrfTokenCheck) {
		$tokenIsValid = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()->validateToken(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('ajaxToken'), 'ajaxCall', $ajaxID);
	}
	if ($tokenIsValid) {
		// Cleanup global variable space
		unset($csrfTokenCheck, $ajaxRegistryEntry, $tokenIsValid, $success);
		$success = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($ajaxScript, $ajaxParams, $ajaxObj, FALSE, TRUE);
	} else {
		$ajaxObj->setError('Invalid CSRF token detected for ajaxID "' . $ajaxID . '"!');
	}
	if ($success === FALSE) {
		$ajaxObj->setError('Registered backend function for ajaxID "' . $ajaxID . '" was not found.');
	}
}

// Outputting the content (and setting the X-JSON-Header)
$ajaxObj->render();
