<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
