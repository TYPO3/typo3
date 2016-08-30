<?php
namespace TYPO3\CMS\Backend\Backend\ToolbarItems;

/*
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

use TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Adds backend live search to the toolbar
 */
class LiveSearchToolbarItem implements ToolbarItemInterface
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/LiveSearch');
    }

    /**
     * Checks whether the user has access to this toolbar item,
     * only allowed when the list module is available
     *
     * @return bool TRUE if user has access, FALSE if not
     */
    public function checkAccess()
    {
        /** @var BackendModuleRepository $backendModuleRepository */
        $backendModuleRepository = GeneralUtility::makeInstance(BackendModuleRepository::class);
        /** @var \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule $listModule */

        // Live search is heavily dependent on the list module and only available when that module is.
        $listModule = $backendModuleRepository->findByModuleName('web_list');
        return $listModule !== null;
    }

    /**
     * Render search field
     *
     * @return string Live search form HTML
     */
    public function getItem()
    {
        return '
			<form class="typo3-topbar-navigation-search t3js-topbar-navigation-search live-search-wrapper" role="search">
				<div class="form-group">
					<input type="text" class="form-control t3js-topbar-navigation-search-field" placeholder="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.search', true) . '" id="live-search-box" autocomplete="off">
				</div>
			</form>
			<div class="dropdown-menu" role="menu"></div>
		';
    }

    /**
     * This item needs to additional attributes
     *
     * @return array
     */
    public function getAdditionalAttributes()
    {
        return ['class' => 'dropdown'];
    }

    /**
     * This item has no drop down
     *
     * @return bool
     */
    public function hasDropDown()
    {
        return false;
    }

    /**
     * No drop down here
     *
     * @return string
     */
    public function getDropDown()
    {
        return '';
    }

    /**
     * Position relative to others, live search should be very right
     *
     * @return int
     */
    public function getIndex()
    {
        return 90;
    }

    /**
     * Returns current PageRenderer
     *
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * Returns LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
