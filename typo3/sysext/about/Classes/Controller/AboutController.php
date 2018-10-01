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

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Module 'about' shows some standard information for TYPO3 CMS:
 * About-text, version number, available modules and so on.
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class AboutController
{
    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var ViewInterface
     */
    protected $view;

    /**
     * Main action: Show standard information
     *
     * @return ResponseInterface the HTML output
     */
    public function indexAction(): ResponseInterface
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->initializeView('index');
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
            'loadedExtensions' => $this->getLoadedExtensions(),
            'copyRightNotice' => BackendUtility::TYPO3_copyRightNotice(),
            'warnings' => $warnings,
            'modules' => $this->getModulesData()
        ]);

        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Create array with data of all main modules (Web, File, ...)
     * and its nested sub modules
     *
     * @return array
     */
    protected function getModulesData(): array
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
    protected function getSubModuleData(ModuleLoader $loadedModules, $moduleName): array
    {
        if (empty($loadedModules->modules[$moduleName]['sub'])) {
            return [];
        }

        $subModulesData = [];
        foreach ($loadedModules->modules[$moduleName]['sub'] as $subModuleName => $subModuleInfo) {
            $moduleLabels = $loadedModules->getLabelsForModule($moduleName . '_' . $subModuleName);
            $subModuleData = [];
            $subModuleData['name'] = $subModuleName;
            $subModuleData['icon'] = $subModuleInfo['icon'] ?? null;
            $subModuleData['iconIdentifier'] = $subModuleInfo['iconIdentifier'] ?? null;
            $subModuleData['label'] = $moduleLabels['title'] ?? null;
            $subModuleData['shortDescription'] = $moduleLabels['shortdescription'] ?? null;
            $subModuleData['longDescription'] = $moduleLabels['description'] ?? null;
            $subModulesData[] = $subModuleData;
        }
        return $subModulesData;
    }

    /**
     * Fetches a list of all active (loaded) extensions in the current system
     *
     * @return array
     */
    protected function getLoadedExtensions(): array
    {
        $extensions = [];
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        foreach ($packageManager->getActivePackages() as $package) {
            // Skip system extensions (= type: typo3-cms-framework)
            if ($package->getValueFromComposerManifest('type') !== 'typo3-cms-extension') {
                continue;
            }
            $extensions[] = [
                'key' => $package->getPackageKey(),
                'title' => $package->getPackageMetaData()->getDescription(),
                'authors' => $package->getValueFromComposerManifest('authors')
            ];
        }
        return $extensions;
    }

    /**
     * Initializes the view by setting the templateName
     *
     * @param string $templateName
     */
    protected function initializeView(string $templateName)
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplate($templateName);
        $this->view->setTemplateRootPaths(['EXT:about/Resources/Private/Templates/About']);
        $this->view->setPartialRootPaths(['EXT:about/Resources/Private/Partials']);
        $this->view->setLayoutRootPaths(['EXT:about/Resources/Private/Layouts']);
    }
}
