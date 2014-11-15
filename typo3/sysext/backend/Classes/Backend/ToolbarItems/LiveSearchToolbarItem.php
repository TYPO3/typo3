<?php
namespace TYPO3\CMS\Backend\Backend\ToolbarItems;

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

use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Adds backend live search to the toolbar
 *
 * @author Michael Klapper <michael.klapper@aoemedia.de>
 * @author Jeff Segars <jeff@webempoweredchurch.org>
 */
class LiveSearchToolbarItem implements ToolbarItemInterface {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->getPageRenderer()->addJsFile('sysext/backend/Resources/Public/JavaScript/livesearch.js');
	}

	/**
	 * Checks whether the user has access to this toolbar item
	 *
	 * @return bool TRUE if user has access, FALSE if not
	 */
	public function checkAccess() {
		$access = FALSE;
		// Loads the backend modules available for the logged in user.
		$loadModules = GeneralUtility::makeInstance(ModuleLoader::class);
		$loadModules->observeWorkspaces = TRUE;
		$loadModules->load($GLOBALS['TBE_MODULES']);
		// Live search is heavily dependent on the list module and only available when that module is.
		if (is_array($loadModules->modules['web']['sub']['list'])) {
			$access = TRUE;
		}
		return $access;
	}

	/**
	 * Render search field
	 *
	 * @return string Live search form HTML
	 */
	public function getItem() {
		return '
			<form class="navbar-form" role="search">
				<div class="live-search-wrapper">
					<div class="form-group">
						<input type="text" class="form-control" placeholder="Search" id="live-search-box">
					</div>
				</div>
			</form>
		';
	}

	/**
	 * This item needs to additional attributes
	 *
	 * @return array
	 */
	public function getAdditionalAttributes() {
		return array();
	}

	/**
	 * This item has no drop down
	 *
	 * @return bool
	 */
	public function hasDropDown() {
		return FALSE;
	}

	/**
	 * No drop down here
	 *
	 * @return string
	 */
	public function getDropDown() {
		return '';
	}

	/**
	 * Position relative to others, live search should be very right
	 *
	 * @return int
	 */
	public function getIndex() {
		return 90;
	}

	/**
	 * Returns current PageRenderer
	 *
	 * @return \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected function getPageRenderer() {
		/** @var  \TYPO3\CMS\Backend\Template\DocumentTemplate $documentTemplate */
		$documentTemplate = $GLOBALS['TBE_TEMPLATE'];
		return $documentTemplate->getPageRenderer();
	}

}
