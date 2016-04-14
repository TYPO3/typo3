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

use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Lang\LanguageService;

/**
 * 'About modules' script - the default start-up module.
 * Will display the list of main- and sub-modules available to the user.
 * Each module will be show with description and a link to the module.
 */
class ModulesController extends ActionController
{
    /**
     * Language Service property. Used to access localized labels
     *
     * @var LanguageService
     */
    protected $languageService;

    /**
     * BackendTemplateView Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @param LanguageService $languageService Language Service to inject
     */
    public function __construct(LanguageService $languageService = null)
    {
        parent::__construct();
        $this->languageService = $languageService ?: $GLOBALS['LANG'];
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
        $view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/EqualHeight');
    }

    /**
     * Show general information and the installed modules
     *
     * @return void
     */
    public function indexAction()
    {
        $warnings = array();
        $securityWarnings = '';
        // Hook for additional warnings
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'] as $classRef) {
                $hookObj = GeneralUtility::getUserObj($classRef);
                if (method_exists($hookObj, 'displayWarningMessages_postProcess')) {
                    $hookObj->displayWarningMessages_postProcess($warnings);
                }
            }
        }
        if (!empty($warnings)) {
            if (count($warnings) > 1) {
                $securityWarnings = '<ul><li>' . implode('</li><li>', $warnings) . '</li></ul>';
            } else {
                $securityWarnings = '<p>' . implode('', $warnings) . '</p>';
            }
            unset($warnings);
        }

        $this->view->assignMultiple(
            array(
                'TYPO3Version' => TYPO3_version,
                'copyRightNotice' => BackendUtility::TYPO3_copyRightNotice(),
                'warningMessages' => $securityWarnings,
                'warningTitle' => $this->languageService->sL('LLL:EXT:lang/locallang_core.xlf:warning.header'),
                'modules' => $this->getModulesData()
            )
        );
    }

    /**
     * Create array with data of all main modules (Web, File, ...)
     * and its nested sub modules
     *
     * @return array
     */
    protected function getModulesData()
    {
        /** @var $loadedModules ModuleLoader */
        $loadedModules = GeneralUtility::makeInstance(ModuleLoader::class);
        $loadedModules->observeWorkspaces = true;
        $loadedModules->load($GLOBALS['TBE_MODULES']);
        $mainModulesData = array();
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
        $subModulesData = array();
        foreach ($loadedModules->modules[$moduleName]['sub'] as $subModuleName => $subModuleInfo) {
            $moduleLabels = $loadedModules->getLabelsForModule($moduleName . '_' . $subModuleName);
            $subModuleData = array();
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
