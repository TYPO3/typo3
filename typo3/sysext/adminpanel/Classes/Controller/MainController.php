<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Controller;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\Modules\AdminPanelModuleInterface;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Adminpanel\Service\ModuleLoader;
use TYPO3\CMS\Adminpanel\View\AdminPanelView;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Main controller for the admin panel
 *
 * @internal
 */
class MainController implements SingletonInterface
{
    /**
     * @var AdminPanelModuleInterface[]
     */
    protected $modules = [];

    /**
     * @var ModuleLoader
     */
    protected $moduleLoader;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * @var array
     */
    private $adminPanelModuleConfiguration;

    /**
     * @param ModuleLoader $moduleLoader
     * @param UriBuilder $uriBuilder
     * @param ConfigurationService $configurationService
     */
    public function __construct(
        ModuleLoader $moduleLoader = null,
        UriBuilder $uriBuilder = null,
        ConfigurationService $configurationService = null
    ) {
        $this->moduleLoader = $moduleLoader ?? GeneralUtility::makeInstance(ModuleLoader::class);
        $this->uriBuilder = $uriBuilder ?? GeneralUtility::makeInstance(UriBuilder::class);
        $this->configurationService = $configurationService ?? GeneralUtility::makeInstance(ConfigurationService::class);
        $this->adminPanelModuleConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules'] ?? [];
    }

    /**
     * Initializes settings for the admin panel.
     *
     * @param ServerRequestInterface $request
     */
    public function initialize(ServerRequestInterface $request): void
    {
        $this->modules = $this->moduleLoader->validateSortAndInitializeModules(
            $this->adminPanelModuleConfiguration
        );
        $this->configurationService->saveConfiguration($this->modules, $request);

        if ($this->isAdminPanelActivated()) {
            foreach ($this->modules as $module) {
                if ($module->isEnabled()) {
                    $subModules = $this->moduleLoader->validateSortAndInitializeSubModules(
                        $this->adminPanelModuleConfiguration[$module->getIdentifier()]['submodules'] ?? []
                    );
                    foreach ($subModules as $subModule) {
                        $subModule->initializeModule($request);
                    }
                    $module->setSubModules($subModules);
                    $module->initializeModule($request);
                }
            }
        }
    }

    /**
     * Renders the admin panel
     *
     * @return string
     */
    public function render(): string
    {
        // legacy handling, deprecated, will be removed in TYPO3 v10.0.
        $adminPanelView = GeneralUtility::makeInstance(AdminPanelView::class);
        $hookObjectContent = $adminPanelView->callDeprecatedHookObject();
        // end legacy handling

        $resources = $this->getResources();

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:adminpanel/Resources/Private/Templates/Main.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);
        $view->setLayoutRootPaths(['EXT:adminpanel/Resources/Private/Layouts']);

        $view->assignMultiple(
            [
                'toggleActiveUrl' => $this->generateBackendUrl('ajax_adminPanel_toggle'),
                'resources' => $resources,
                'adminPanelActive' => $this->isAdminPanelActivated(),
            ]
        );
        if ($this->isAdminPanelActivated()) {
            $moduleResources = $this->getAdditionalResourcesForModules($this->modules);
            $view->assignMultiple(
                [
                    'modules' => $this->modules,
                    'hookObjectContent' => $hookObjectContent,
                    'saveUrl' => $this->generateBackendUrl('ajax_adminPanel_saveForm'),
                    'moduleResources' => $moduleResources,
                ]
            );
        }

        return $view->render();
    }

    /**
     * Generate a url to a backend route
     *
     * @param string $route
     * @return string
     */
    protected function generateBackendUrl(string $route): string
    {
        return (string)$this->uriBuilder->buildUriFromRoute($route);
    }

    /**
     * Get additional resources (css, js) from modules and merge it to
     * one array - returns an array of full html tags
     *
     * @param AdminPanelModuleInterface[] $modules
     * @return array
     */
    protected function getAdditionalResourcesForModules(array $modules): array
    {
        $result = [
            'js' => '',
            'css' => '',
        ];
        foreach ($modules as $module) {
            foreach ($module->getJavaScriptFiles() as $file) {
                $result['js'] .= $this->getJsTag($file);
            }
            foreach ($module->getCssFiles() as $file) {
                $result['css'] .= $this->getCssTag($file);
            }
        }
        return $result;
    }

    /**
     * Returns a link tag with the admin panel stylesheet
     * defined using TBE_STYLES
     *
     * @return string
     */
    protected function getAdminPanelStylesheet(): string
    {
        $result = '';
        if (!empty($GLOBALS['TBE_STYLES']['stylesheets']['admPanel'])) {
            $stylesheet = GeneralUtility::locationHeaderUrl($GLOBALS['TBE_STYLES']['stylesheets']['admPanel']);
            $result = '<link rel="stylesheet" type="text/css" href="' .
                      htmlspecialchars($stylesheet, ENT_QUOTES | ENT_HTML5) . '" />';
        }
        return $result;
    }

    /**
     * Returns the current BE user.
     *
     * @return FrontendBackendUserAuthentication
     */
    protected function getBackendUser(): FrontendBackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Get a css tag for file - with absolute web path resolving
     *
     * @param string $cssFileLocation
     * @return string
     */
    protected function getCssTag(string $cssFileLocation): string
    {
        $css = '<link type="text/css" rel="stylesheet" href="' .
               htmlspecialchars(
                   PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName($cssFileLocation)),
                   ENT_QUOTES | ENT_HTML5
               ) .
               '" media="all" />';
        return $css;
    }

    /**
     * Get a script tag for JavaScript with absolute paths
     *
     * @param string $jsFileLocation
     * @return string
     */
    protected function getJsTag(string $jsFileLocation): string
    {
        $js = '<script type="text/javascript" src="' .
              htmlspecialchars(
                  PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName($jsFileLocation)),
                  ENT_QUOTES | ENT_HTML5
              ) .
              '"></script>';
        return $js;
    }

    /**
     * Return a string with tags for main admin panel resources
     *
     * @return string
     */
    protected function getResources(): string
    {
        $jsFileLocation = 'EXT:adminpanel/Resources/Public/JavaScript/AdminPanel.js';
        $js = $this->getJsTag($jsFileLocation);
        $cssFileLocation = 'EXT:adminpanel/Resources/Public/Css/adminpanel.css';
        $css = $this->getCssTag($cssFileLocation);

        return $css . $this->getAdminPanelStylesheet() . $js;
    }

    /**
     * Returns true if admin panel was activated
     * (switched "on" via GUI)
     *
     * @return bool
     */
    protected function isAdminPanelActivated(): bool
    {
        return (bool)($this->getBackendUser()->uc['AdminPanel']['display_top'] ?? false);
    }
}
