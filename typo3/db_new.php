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
 * New database item menu
 *
 * This script lets users choose a new database element to create.
 * Includes a wizard mode for visually pointing out the position of new pages
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
require __DIR__ . '/init.php';

/**
 * Extension for the tree class that generates the tree of pages in the page-wizard mode
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class newRecordLocalPageTree extends \TYPO3\CMS\Backend\Tree\View\PageTreeView {

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

$newRecordController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Controller\\NewRecordController');
$newRecordController->main();
$newRecordController->printContent();
