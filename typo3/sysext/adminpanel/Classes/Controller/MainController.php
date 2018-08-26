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
use TYPO3\CMS\Adminpanel\ModuleApi\ConfigurableInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\InitializableInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleDataStorageCollection;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\PageSettingsProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ShortInfoProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\SubmoduleProviderInterface;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Adminpanel\Service\ModuleLoader;
use TYPO3\CMS\Adminpanel\Utility\ResourceUtility;
use TYPO3\CMS\Adminpanel\Utility\StateUtility;
use TYPO3\CMS\Adminpanel\View\AdminPanelView;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Main controller for the admin panel
 *
 * @internal
 */
class MainController implements SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Adminpanel\ModuleApi\ModuleInterface[]
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
    protected $adminPanelModuleConfiguration;

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
        $this->configurationService = $configurationService
                                      ??
                                      GeneralUtility::makeInstance(ConfigurationService::class);
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

        if (StateUtility::isActivatedForUser()) {
            $this->initializeModules($request, $this->modules);
        }
    }

    /**
     * Renders the admin panel - Called in PSR-15 Middleware
     *
     * @see \TYPO3\CMS\Adminpanel\Middleware\AdminPanelRenderer
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return string
     */
    public function render(ServerRequestInterface $request): string
    {
        // legacy handling, deprecated, will be removed in TYPO3 v10.0.
        $adminPanelView = GeneralUtility::makeInstance(AdminPanelView::class);
        $hookObjectContent = $adminPanelView->callDeprecatedHookObject();
        // end legacy handling

        $resources = ResourceUtility::getResources();

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:adminpanel/Resources/Private/Templates/Main.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);
        $view->setLayoutRootPaths(['EXT:adminpanel/Resources/Private/Layouts']);

        $view->assignMultiple(
            [
                'toggleActiveUrl' => $this->generateBackendUrl('ajax_adminPanel_toggle'),
                'resources' => $resources,
                'adminPanelActive' => StateUtility::isOpen(),
            ]
        );
        if (StateUtility::isOpen()) {
            $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('adminpanel_requestcache');
            $requestId = $request->getAttribute('adminPanelRequestId');
            $data = $cache->get($requestId);
            $moduleResources = ResourceUtility::getAdditionalResourcesForModules($this->modules);
            $settingsModules = array_filter($this->modules, function (ModuleInterface $module) {
                return $module instanceof PageSettingsProviderInterface;
            });
            $parentModules = array_filter(
                $this->modules,
                function (ModuleInterface $module) {
                    return $module instanceof SubmoduleProviderInterface && $module instanceof ShortInfoProviderInterface;
                }
            );
            $view->assignMultiple(
                [
                    'modules' => $this->modules,
                    'settingsModules' => $settingsModules,
                    'parentModules' => $parentModules,
                    'hookObjectContent' => $hookObjectContent,
                    'saveUrl' => $this->generateBackendUrl('ajax_adminPanel_saveForm'),
                    'moduleResources' => $moduleResources,
                    'requestId' => $requestId,
                    'data' => $data ?? [],
                ]
            );
        }
        return $view->render();
    }

    /**
     * Stores data for admin panel in cache - Called in PSR-15 Middleware
     *
     * @see \TYPO3\CMS\Adminpanel\Middleware\AdminPanelDataPersister
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    public function storeData(ServerRequestInterface $request): void
    {
        if (StateUtility::isOpen()) {
            $data = $this->storeDataPerModule(
                $request,
                $this->modules,
                GeneralUtility::makeInstance(ModuleDataStorageCollection::class)
            );
            $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('adminpanel_requestcache');
            $cache->set($request->getAttribute('adminPanelRequestId'), $data);
            $cache->collectGarbage();
        }
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
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \TYPO3\CMS\Adminpanel\ModuleApi\ModuleInterface[] $modules
     */
    protected function initializeModules(ServerRequestInterface $request, array $modules): void
    {
        foreach ($modules as $module) {
            if (
                ($module instanceof InitializableInterface)
                && (
                    (($module instanceof ConfigurableInterface) && $module->isEnabled())
                    || (!($module instanceof ConfigurableInterface))
                )
            ) {
                $module->initializeModule($request);
            }
            if ($module instanceof SubmoduleProviderInterface) {
                $this->initializeModules($request, $module->getSubModules());
            }
        }
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \TYPO3\CMS\Adminpanel\ModuleApi\ModuleInterface[] $modules
     * @param ModuleDataStorageCollection $data
     * @return ModuleDataStorageCollection
     */
    protected function storeDataPerModule(ServerRequestInterface $request, array $modules, ModuleDataStorageCollection $data): ModuleDataStorageCollection
    {
        foreach ($modules as $module) {
            if (
                ($module instanceof DataProviderInterface)
                && (
                    (($module instanceof ConfigurableInterface) && $module->isEnabled())
                    || (!($module instanceof ConfigurableInterface))
                )
            ) {
                $data->addModuleData($module, $module->getDataToStore($request));
            }

            if ($module instanceof SubmoduleProviderInterface) {
                $this->storeDataPerModule($request, $module->getSubModules(), $data);
            }
        }
        return $data;
    }
}
