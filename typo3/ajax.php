<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Benjamin Mack
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
	'BackendLogin::getChallenge'
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

require 'init.php';

// finding the script path from the variable
$ajaxScript = $TYPO3_CONF_VARS['BE']['AJAX'][$ajaxID];
// Instantiating the AJAX object
$ajaxObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Http\\AjaxRequestHandler', $ajaxID);
$ajaxParams = array();
// Evaluating the arguments and calling the AJAX method/function
if (empty($ajaxID)) {
	$ajaxObj->setError('No valid ajaxID parameter given.');
} elseif (empty($ajaxScript)) {
	$ajaxObj->setError('No backend function registered for ajaxID "' . $ajaxID . '".');
} else {
	$ret = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($ajaxScript, $ajaxParams, $ajaxObj, FALSE, TRUE);
	if ($ret === FALSE) {
		$ajaxObj->setError('Registered backend function for ajaxID "' . $ajaxID . '" was not found.');
	}
}
// Outputting the content (and setting the X-JSON-Header)
$ajaxObj->render();
?>