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
 * Wizard for inserting TSconfig in form fields. (page,user or TS)
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
$GLOBALS['BACK_PATH'] = '';
require __DIR__ . '/init.php';
$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_wizards.xlf');
/**
 * TypoScript parser extension class.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ext_TSparser extends \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService {

	/**
	 * Pass through of incoming value for link.
	 *
	 * @param array $P P array
	 * @return string The "_LINK" key value, straight away.
	 * @todo Define visibility
	 */
	public function makeHtmlspecialchars($P) {
		return $P['_LINK'];
	}

}

/*
 * @deprecated since 6.0, the classname SC_wizard_tsconfig and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/backend/Classes/Wizard/TsconfigWizard.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('backend') . 'Classes/Wizard/TsconfigWizard.php';
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Wizard\\TsconfigWizard');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>