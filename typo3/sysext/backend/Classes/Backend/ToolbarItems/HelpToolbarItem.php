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

use TYPO3\CMS\Backend\Domain\Model\Module\BackendModule;
use TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        return $this->iconFactory->getIcon('apps-toolbar-menu-help', Icon::SIZE_SMALL)->render('inline');
    }

    /**
     * Render drop down
     *
     * @return string
     */
    public function getDropDown()
    {
        $dropdown = [];
        $dropdown[] = '<ul class="dropdown-list">';
        foreach ($this->helpModuleMenu->getChildren() as $module) {
            /** @var BackendModule $module */
            $moduleIcon = $module->getIcon();
            $dropdown[] ='<li'
                . ' id="' . htmlspecialchars($module->getName()) . '"'
                . ' class="typo3-module-menu-item submodule mod-' . htmlspecialchars($module->getName()) . '" '
                . ' data-modulename="' . htmlspecialchars($module->getName()) . '"'
                . ' data-navigationcomponentid="' . htmlspecialchars($module->getNavigationComponentId()) . '"'
                . ' data-navigationframescript="' . htmlspecialchars($module->getNavigationFrameScript()) . '"'
                . ' data-navigationframescriptparameters="' . htmlspecialchars($module->getNavigationFrameScriptParameters()) . '"'
                . '>';
            $dropdown[] = '<a title="' . htmlspecialchars($module->getDescription()) . '" href="' . htmlspecialchars($module->getLink()) . '" class="dropdown-list-link modlink">';
            $dropdown[] = '<span class="submodule-icon typo3-app-icon"><span><span>' . $moduleIcon . '</span></span></span>';
            $dropdown[] = '<span class="submodule-label">' . htmlspecialchars($module->getTitle()) . '</span>';
            $dropdown[] = '</a>';
            $dropdown[] = '</li>';
        }
        $dropdown[] = '</ul>';
        return implode(LF, $dropdown);
    }

    /**
     * No additional attributes needed.
     *
     * @return array
     */
    public function getAdditionalAttributes()
    {
        return ['class' => 'typo3-module-menu-group'];
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
}
