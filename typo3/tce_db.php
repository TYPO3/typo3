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
 * TCE gateway (TYPO3 Core Engine) for database handling
 * This script is a gateway for POST forms to \TYPO3\CMS\Core\DataHandling\DataHandler
 * that manipulates all information in the database!!
 * For syntax and API information, see the document 'TYPO3 Core APIs'
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
require __DIR__ . '/init.php';

$simpleDataHandlerController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Controller\\SimpleDataHandlerController');

$formprotection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
if ($formprotection->validateToken(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('formToken'), 'tceAction')) {
	$simpleDataHandlerController->initClipboard();
	$simpleDataHandlerController->main();
}
$simpleDataHandlerController->finish();
