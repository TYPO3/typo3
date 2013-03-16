<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Stefan Galinski <stefan.galinski@gmail.com>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
if (!defined('PATH_typo3conf')) {
	die('Could not access this script directly!');
}
$extDirectEidInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Controller\\ExtDirectEidController');
if ($extDirectEidInstance->actionIsAllowed()) {
	$extDirectEidInstance->routeAction();
	$extDirectEidInstance->render();
}
?>