<?php
namespace TYPO3\CMS\About\Controller;

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

use TYPO3\CMS\About\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Module 'about' shows some standard information for TYPO3 CMS: About-text, version number, available modules and so on.
 */
class AboutController extends ActionController
{
    /**
     * @var ViewInterface
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @var ExtensionRepository
     */
    protected $extensionRepository;

    /**
     * @param ExtensionRepository $extensionRepository
     */
    public function injectExtensionRepository(ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
        // Disable Path
        $view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation([]);
    }

    /**
     * Main action: Show standard information
     */
    public function indexAction()
    {
        $warnings = [];
        // Hook for additional warnings
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'displayWarningMessages_postProcess')) {
                $hookObj->displayWarningMessages_postProcess($warnings);
            }
        }

        $this->view->assignMultiple([
            'copyrightYear' => TYPO3_copyright_year,
            'donationUrl' => TYPO3_URL_DONATE,
            'currentVersion' => TYPO3_version,
            'loadedExtensions' => $this->extensionRepository->findAllLoaded(),
            'copyRightNotice' => BackendUtility::TYPO3_copyRightNotice(),
            'warnings' => $warnings,
            'modules' => $this->getModulesData()
        ]);
    }

    /**
     * Create array with data of all main modules (Web, File, ...)
     * and its nested sub modules
     *
     * @return array
     */
    protected function getModulesData()
    {
        $loadedModules = GeneralUtility::makeInstance(ModuleLoader::class);
        $loadedModules->observeWorkspaces = true;
        $loadedModules->load($GLOBALS['TBE_MODULES']);
        $mainModulesData = [];
        foreach ($loadedModules->modules as $moduleName => $moduleInfo) {
            $moduleLabels = $loadedModules->getLabelsForModule($moduleName);
            $mainModuleData = [
                'name'  => $moduleName,
                'label' => $moduleLabels['title']
            ];
            if (is_array($moduleInfo['sub']) && !empty($moduleInfo['sub'])) {
                $mainModuleData['subModules'] = $this->getSubModuleData($loadedModules, $moduleName);
            }
            $mainModulesData[] = $mainModuleData;
        }
        return $mainModulesData;
    }

    /**
     * Create array with data of all subModules of a specific main module
     *
     * @param ModuleLoader $loadedModules the module loader instance
     * @param string $moduleName Name of the main module
     * @return array
     */
    protected function getSubModuleData(ModuleLoader $loadedModules, $moduleName)
    {
        $subModulesData = [];
        foreach ($loadedModules->modules[$moduleName]['sub'] as $subModuleName => $subModuleInfo) {
            $moduleLabels = $loadedModules->getLabelsForModule($moduleName . '_' . $subModuleName);
            $subModuleData = [];
            $subModuleData['name'] = $subModuleName;
            $subModuleData['icon'] = $subModuleInfo['icon'];
            $subModuleData['iconIdentifier'] = $subModuleInfo['iconIdentifier'];
            $subModuleData['label'] = $moduleLabels['title'];
            $subModuleData['shortDescription'] = $moduleLabels['shortdescription'];
            $subModuleData['longDescription'] = $moduleLabels['description'];
            $subModulesData[] = $subModuleData;
        }
        return $subModulesData;
    }
}
