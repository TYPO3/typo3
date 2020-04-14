<?php

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

namespace TYPO3\CMS\Backend\Backend\ToolbarItems;

use TYPO3\CMS\Backend\Backend\Shortcut\ShortcutRepository;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class to render the shortcut menu
 *
 * @internal This class is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
class ShortcutToolbarItem implements ToolbarItemInterface
{
    /**
     * @var ShortcutRepository
     */
    protected $shortcutRepository;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->shortcutRepository = GeneralUtility::makeInstance(ShortcutRepository::class);

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Toolbar/ShortcutMenu');
        $languageService = $this->getLanguageService();
        $languageFile = 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf';
        $pageRenderer->addInlineLanguageLabelArray([
            'bookmark.delete' => $languageService->sL($languageFile . ':toolbarItems.bookmarksDelete'),
            'bookmark.confirmDelete' => $languageService->sL($languageFile . ':toolbarItems.confirmBookmarksDelete'),
            'bookmark.create' => $languageService->sL($languageFile . ':toolbarItems.createBookmark'),
            'bookmark.savedTitle' => $languageService->sL($languageFile . ':toolbarItems.bookmarkSavedTitle'),
            'bookmark.savedMessage' => $languageService->sL($languageFile . ':toolbarItems.bookmarkSavedMessage'),
        ]);
    }

    /**
     * Checks whether the user has access to this toolbar item
     *
     * @return bool TRUE if user has access, FALSE if not
     */
    public function checkAccess()
    {
        return (bool)($this->getBackendUser()->getTSConfig()['options.']['enableBookmarks'] ?? false);
    }

    /**
     * Render shortcut icon
     *
     * @return string HTML
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     * @throws \InvalidArgumentException
     */
    public function getItem()
    {
        return $this->getFluidTemplateObject('Item.html')->render();
    }

    /**
     * This item has a drop down
     *
     * @return bool
     */
    public function hasDropDown()
    {
        return true;
    }

    /**
     * Render drop down content
     *
     * @return string HTML
     */
    public function getDropDown()
    {
        $shortcutMenu = [];
        $groups = $this->shortcutRepository->getGroupsFromShortcuts();
        arsort($groups, SORT_NUMERIC);

        foreach ($groups as $groupId => $groupLabel) {
            $shortcutMenu[] = [
                'id' => (int)$groupId,
                'title' => $groupLabel,
                'shortcuts' => $this->shortcutRepository->getShortcutsByGroup($groupId),
            ];
        }

        $dropDownView = $this->getFluidTemplateObject('DropDown.html');
        $dropDownView->assign('shortcutMenu', $shortcutMenu);

        return $dropDownView->render();
    }

    /**
     * This toolbar item needs no additional attributes
     *
     * @return array
     */
    public function getAdditionalAttributes()
    {
        return [];
    }

    /**
     * Position relative to others, live search should be very right
     *
     * @return int
     */
    public function getIndex()
    {
        return 20;
    }

    /**
     * returns a new standalone view, shorthand function
     *
     * @param string $templateFilename
     * @return StandaloneView
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     * @throws \InvalidArgumentException
     * @internal param string $templateFile
     */
    protected function getFluidTemplateObject(string $templateFilename): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
        $view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates/ShortcutToolbarItem']);
        $view->setTemplate($templateFilename);
        $view->getRequest()->setControllerExtensionName('Backend');

        return $view;
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
