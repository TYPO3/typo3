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
 * New database item menu
 *
 * This script lets users choose a new database element to create.
 * Includes a wizard mode for visually pointing out the position of new pages
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
$BACK_PATH = '';
require 'init.php';
$LANG->includeLLFile('EXT:lang/locallang_misc.xlf');
/**
 * Extension for the tree class that generates the tree of pages in the page-wizard mode
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class localPageTree extends \TYPO3\CMS\Backend\Tree\View\PageTreeView {

	/**
	 * Inserting uid-information in title-text for an icon
	 *
	 * @param string $icon Icon image
	 * @param array $row Item row
	 * @return string Wrapping icon image.
	 * @todo Define visibility
	 */
	public function wrapIcon($icon, $row) {
		return $this->addTagAttributes($icon, ' title="id=' . htmlspecialchars($row['uid']) . '"');
	}

	/**
	 * Determines whether to expand a branch or not.
	 * Here the branch is expanded if the current id matches the global id for the listing/new
	 *
	 * @param integer $id The ID (page id) of the element
	 * @return boolean Returns TRUE if the IDs matches
	 * @todo Define visibility
	 */
	public function expandNext($id) {
		return $id == $GLOBALS['SOBE']->id ? 1 : 0;
	}

}

/*
 * @deprecated since 6.0, the classname SC_db_new and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/backend/Classes/Controller/NewRecordController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('backend') . 'Classes/Controller/NewRecordController.php';
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Controller\\NewRecordController');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>