<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Module 'about' shows some standard information for TYPO3 CMS:
 * About-text, version number, available modules and so on.
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class AboutController
{
    public function __construct(
        protected readonly Typo3Version $version,
        protected readonly Typo3Information $typo3Information,
        protected readonly ModuleLoader $moduleLoader,
        protected readonly PackageManager $packageManager,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->moduleLoader->observeWorkspaces = true;
        $this->moduleLoader->load($GLOBALS['TBE_MODULES']);
    }

    /**
     * Main action: Show standard information
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $warnings = [];
        // Hook for additional warnings
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'displayWarningMessages_postProcess')) {
                $hookObj->displayWarningMessages_postProcess($warnings);
            }
        }
        $view = $this->moduleTemplateFactory->create($request, 'typo3/cms-backend');
        $view->assignMultiple([
            'copyrightYear' => $this->typo3Information->getCopyrightYear(),
            'donationUrl' => $this->typo3Information::URL_DONATE,
            'currentVersion' => $this->version->getVersion(),
            'loadedExtensions' => $this->getLoadedExtensions(),
            'copyRightNotice' => $this->typo3Information->getCopyrightNotice(),
            'warnings' => $warnings,
            'modules' => $this->getModulesData(),
        ]);
        return $view->renderResponse('About/Index');
    }

    /**
     * Create array with data of all main modules (Web, File, ...)
     * and its nested sub modules.
     */
    protected function getModulesData(): array
    {
        $mainModulesData = [];
        foreach ($this->moduleLoader->getModules() as $moduleName => $moduleInfo) {
            $moduleLabels = $this->moduleLoader->getLabelsForModule($moduleName);
            $mainModuleData = [
                'name'  => $moduleName,
                'label' => $moduleLabels['title'],
            ];
            if (is_array($moduleInfo['sub'] ?? null) && !empty($moduleInfo['sub'])) {
                $mainModuleData['subModules'] = $this->getSubModuleData((string)$moduleName);
            }
            $mainModulesData[] = $mainModuleData;
        }
        return $mainModulesData;
    }

    /**
     * Create array with data of all subModules of a specific main module
     */
    protected function getSubModuleData(string $moduleName): array
    {
        if (empty($this->moduleLoader->getModules()[$moduleName]['sub'])) {
            return [];
        }
        $subModulesData = [];
        foreach ($this->moduleLoader->getModules()[$moduleName]['sub'] ?? [] as $subModuleName => $subModuleInfo) {
            $moduleLabels = $this->moduleLoader->getLabelsForModule($moduleName . '_' . $subModuleName);
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
     */
    protected function getLoadedExtensions(): array
    {
        $extensions = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            // Skip system extensions
            if ($package->getPackageMetaData()->isFrameworkType()) {
                continue;
            }
            $extensions[] = [
                'key' => $package->getPackageKey(),
                'title' => $package->getPackageMetaData()->getDescription(),
                'authors' => $package->getValueFromComposerManifest('authors'),
            ];
        }
        return $extensions;
    }
}
