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

use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * User toolbar item
 */
class UserToolbarItem implements ToolbarItemInterface
{
    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Item is always enabled
     *
     * @return bool TRUE
     */
    public function checkAccess()
    {
        return true;
    }

    /**
     * Render username
     *
     * @return string HTML
     */
    public function getItem()
    {
        $backendUser = $this->getBackendUser();

        /** @var Avatar $avatar */
        $avatar =  GeneralUtility::makeInstance(Avatar::class);
        $icon = $avatar->render();

        $realName = $backendUser->user['realName'];
        $username = $backendUser->user['username'];

        $view = $this->getFluidTemplateObject('UserToolbarItem.html');
        $view->assignMultiple([
                'username' => $username,
                'realName' => $realName,
                'switchUserMode' => $backendUser->user['ses_backuserid'],
                'icon' => $icon
            ]
        );

        return $view->render();
    }

    /**
     * Render drop down
     *
     * @return string HTML
     */
    public function getDropDown()
    {
        $backendUser = $this->getBackendUser();

        /** @var BackendModuleRepository $backendModuleRepository */
        $backendModuleRepository = GeneralUtility::makeInstance(BackendModuleRepository::class);
        /** @var \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule $userModuleMenu */
        $userModuleMenu = $backendModuleRepository->findByModuleName('user');

        $icon = $this->iconFactory->getIcon('actions-logout', Icon::SIZE_SMALL)->render('inline');

        $view = $this->getFluidTemplateObject('UserToolbarItemDropDown.html');
        $view->assignMultiple([
                'modules' => $userModuleMenu->getChildren(),
                'logoutUrl' => BackendUtility::getModuleUrl('logout'),
                'switchUserMode' => $backendUser->user['ses_backuserid'],
                'icon' => $icon
            ]
        );

        return $view->render();
    }

    /**
     * Returns an additional class if user is in "switch user" mode
     *
     * @return array
     */
    public function getAdditionalAttributes()
    {
        $result = [];
        $result['class'] = 'toolbar-item-user';
        if ($this->getBackendUser()->user['ses_backuserid']) {
            $result['class'] .= ' su-user';
        }
        return $result;
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
     * Position relative to others
     *
     * @return int
     */
    public function getIndex()
    {
        return 80;
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
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $filename Which templateFile should be used.
     *
     * @return StandaloneView
     */
    protected function getFluidTemplateObject(string $filename):StandaloneView
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Layouts')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials/ToolbarItems')]);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/ToolbarItems')]);

        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/ToolbarItems/' . $filename));

        $view->getRequest()->setControllerExtensionName('Backend');
        return $view;
    }
}
