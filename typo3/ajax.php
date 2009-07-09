<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Benjamin Mack
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
 * @author	Benjamin Mack <mack@xnos.org>
 * @package	TYPO3
 */

$TYPO3_AJAX = true;

require('init.php');
require('classes/class.typo3ajax.php');
require_once(PATH_typo3.'sysext/lang/lang.php');


$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
$GLOBALS['LANG']->init($GLOBALS['BE_USER']->uc['lang']);

	// finding the script path from the variable
$ajaxID = (string) t3lib_div::_GP('ajaxID');
$ajaxScript = $TYPO3_CONF_VARS['BE']['AJAX'][$ajaxID];


	// instantiating the AJAX object
$ajaxClassName = t3lib_div::makeInstanceClassName('TYPO3AJAX');
$ajaxObj       = new $ajaxClassName($ajaxID);
$ajaxParams    = array();


	// evaluating the arguments and calling the AJAX method/function
if (empty($ajaxID)) {
	$ajaxObj->setError('No valid ajaxID parameter given.');
} else if (empty($ajaxScript)) {
	$ajaxObj->setError('No backend function registered for ajaxID "'.$ajaxID.'".');
} else {
	$ret = t3lib_div::callUserFunction($ajaxScript, $ajaxParams, $ajaxObj, false, true);
	if ($ret === false) {
		$ajaxObj->setError('Registered backend function for ajaxID "'.$ajaxID.'" was not found.');
	}
}

	// outputting the content (and setting the X-JSON-Header)
$ajaxObj->render();

?>
