<?php
namespace TYPO3\CMS\Backend\Toolbar;

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
 * Adds backend live search to the toolbar
 *
 * @author Michael Klapper <michael.klapper@aoemedia.de>
 * @author Jeff Segars <jeff@webempoweredchurch.org>
 */
class LiveSearchToolbarItem implements ToolbarItemInterface {

	/**
	 * @var \TYPO3\CMS\Backend\Controller\BackendController
	 */
	protected $backendReference;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Backend\Controller\BackendController $backendReference TYPO3 backend object reference
	 */
	public function __construct(\TYPO3\CMS\Backend\Controller\BackendController &$backendReference = NULL) {
		$this->backendReference = $backendReference;
	}

	/**
	 * Checks whether the user has access to this toolbar item
	 *
	 * @return bool TRUE if user has access, FALSE if not
	 */
	public function checkAccess() {
		$access = FALSE;
		// Loads the backend modules available for the logged in user.
		$loadModules = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Module\ModuleLoader::class);
		$loadModules->observeWorkspaces = TRUE;
		$loadModules->load($GLOBALS['TBE_MODULES']);
		// Live search is heavily dependent on the list module and only available when that module is.
		if (is_array($loadModules->modules['web']['sub']['list'])) {
			$access = TRUE;
		}
		return $access;
	}

	/**
	 * Creates the selector for workspaces
	 *
	 * @return string Workspace selector as HTML select
	 */
	public function render() {
		$this->backendReference->addJavascriptFile('sysext/backend/Resources/Public/JavaScript/livesearch.js');

		return '
			<form class="navbar-form" role="search">
				<div class="live-search-wrapper">
					<div class="form-group">
						<div class="input-group">
							<input type="text" class="form-control" placeholder="Search" id="live-search-box">
							<span class="input-group-addon">
								<span title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:search') . '" class="t3-icon t3-icon-apps t3-icon-apps-toolbar t3-icon-toolbar-menu-search">&nbsp;</span>
							</span>
						</div>
					</div>
				</div>
			</form>
		';
	}

	/**
	 * Returns additional attributes for the list item in the toolbar
	 *
	 * This should not contain the "class" or "id" attribute.
	 * Use the methods for setting these attributes
	 *
	 * @return string List item HTML attibutes
	 */
	public function getAdditionalAttributes() {
		return '';
	}

	/**
	 * Return attribute id name
	 *
	 * @return string The name of the ID attribute
	 */
	public function getIdAttribute() {
		return 'live-search-menu';
	}

	/**
	 * Returns extra classes
	 *
	 * @return array
	 */
	public function getExtraClasses() {
		return array();
	}

	/**
	 * Get dropdown
	 *
	 * @return bool
	 */
	public function getDropdown() {
		return FALSE;
	}

}
