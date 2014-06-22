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
 * Main form rendering script
 * By sending certain parameters to this script you can bring up a form
 * which allows the user to edit the content of one or more database records.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
require_once 'init.php';
\TYPO3\CMS\Backend\Utility\BackendUtility::lockRecords();

/* @var $editDocumentController \TYPO3\CMS\Backend\Controller\EditDocumentController */
$editDocumentController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Controller\\EditDocumentController');

// Preprocessing, storing data if submitted to
$editDocumentController->preInit();

// Checks, if a save button has been clicked (or the doSave variable is sent)
if ($editDocumentController->doProcessData()) {
	$formprotection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
	if ($formprotection->validateToken(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('formToken'), 'editRecord')) {
		$editDocumentController->processData();
	}
}

$editDocumentController->init();
$editDocumentController->main();
$editDocumentController->printContent();
