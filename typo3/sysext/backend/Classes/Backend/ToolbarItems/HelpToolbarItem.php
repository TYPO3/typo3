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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Help toolbar item
 */
class HelpToolbarItem implements ToolbarItemInterface
{
    /**
     * @var \SplObjectStorage<BackendModule>
     */
    protected $helpModuleMenu = null;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        /** @var BackendModuleRepository $backendModuleRepository */
        $backendModuleRepository = GeneralUtility::makeInstance(BackendModuleRepository::class);
        /** @var \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule $helpModuleMenu */
        $helpModuleMenu = $backendModuleRepository->findByModuleName('help');
        if ($helpModuleMenu && $helpModuleMenu->getChildren()->count() > 0) {
            $this->helpModuleMenu = $helpModuleMenu;
        }
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Users see this if a module is available
     *
     * @return bool TRUE
     */
    public function checkAccess()
    {
        $result = (bool)$this->helpModuleMenu;
        return $result;
    }

    /**
     * Render help icon
     *
     * @return string Help
     */
    public function getItem()
    {
        $icon = $this->iconFactory->getIcon('apps-toolbar-menu-help', Icon::SIZE_SMALL)->render('inline');

        $view = $this->getFluidTemplateObject('HelpToolbarItem.html');
        $view->assignMultiple([
                'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:toolbarItems.help',
                'icon' => $icon
            ]
        );

        return $view->render();
    }

    /**
     * Render drop down
     *
     * @return string
     */
    public function getDropDown()
    {
        $view = $this->getFluidTemplateObject('HelpToolbarItemDropDown.html');
        $view->assignMultiple([
                'title' =>  'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:rm.clearCache_clearCache',
                'modules' => $this->helpModuleMenu->getChildren()
            ]
        );

        return $view->render();
    }

    /**
     * No additional attributes needed.
     *
     * @return array
     */
    public function getAdditionalAttributes()
    {
        return [];
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
        return 70;
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
