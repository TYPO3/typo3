<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Lowlevel\Controller;

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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Configuration\SiteTcaConfiguration;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Lowlevel\Utility\ArrayBrowser;

/**
 * View configuration arrays in the backend
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class ConfigurationController
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Available trees to render.
     *  * label is an LLL identifier
     *  * type is used to identify the data source type
     *  * globalKey (only for type=global) is the name of a global variable
     *
     * @var array
     */
    protected $treeSetup = [
        'confVars' => [
            'label' => 'typo3ConfVars',
            'type' => 'global',
            'globalKey' => 'TYPO3_CONF_VARS',
        ],
        'tca' => [
            'label' => 'tca',
            'type' => 'global',
            'globalKey' => 'TCA',
        ],
        'tcaDescr' => [
            'label' => 'tcaDescr',
            'type' => 'global',
            'globalKey' => 'TCA_DESCR',
        ],
        'services' => [
            'label' => 't3services',
            'key' => 'services',
            'type' => 'global',
            'globalKey' => 'T3_SERVICES',
        ],
        'tbeModules' => [
            'label' => 'tbemodules',
            'type' => 'global',
            'globalKey' => 'TBE_MODULES',
        ],
        'tbeModulesExt' => [
            'label' => 'tbemodulesext',
            'type' => 'global',
            'globalKey' => 'TBE_MODULES_EXT',
        ],
        'tbeStyles' => [
            'label' => 'tbeStyles',
            'type' => 'global',
            'globalKey' => 'TBE_STYLES',
        ],
        'userSettings' => [
            'label' => 'usersettings',
            'type' => 'global',
            'globalKey' => 'TYPO3_USER_SETTINGS',
        ],
        'pagesTypes' => [
            'label' => 'pagesTypes',
            'type' => 'global',
            'globalKey' => 'PAGES_TYPES',
        ],
        'beUserUc' => [
            'label' => 'beUser',
            'type' => 'uc',
        ],
        'beUserTsConfig' => [
            'label' => 'beUserTsConfig',
            'type' => 'beUserTsConfig',
        ],
        'beRoutes' => [
            'label' => 'routes',
            'type' => 'routes',
        ],
        'httpMiddlewareStacks' => [
            'label' => 'httpMiddlewareStacks',
            'type' => 'httpMiddlewareStacks',
        ],
        'siteConfiguration' => [
            'label' => 'siteConfiguration',
            'type' => 'siteConfiguration',
        ],
    ];

    /**
     * Blind configurations which should not be visible to mortal admins
     *
     * @var array
     */
    protected $blindedConfigurationOptions = [
        'TYPO3_CONF_VARS' => [
            'DB' => [
                'database' => '******',
                'host' => '******',
                'password' => '******',
                'port' => '******',
                'socket' => '******',
                'username' => '******',
                'Connections' => [
                    'Default' => [
                        'dbname' => '******',
                        'host' => '******',
                        'password' => '******',
                        'port' => '******',
                        'user' => '******',
                        'unix_socket' => '******',
                    ],
                ],
            ],
            'SYS' => [
                'encryptionKey' => '******'
            ],
        ],
    ];

    /**
     * Main controller action determines get/post values, takes care of
     * stored backend user settings for this module, determines tree
     * and renders it.
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     * @throws \RuntimeException
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();

        $queryParams = $request->getQueryParams();
        $postValues = $request->getParsedBody();

        $moduleState = $backendUser->uc['moduleData']['system_config'] ?? [];

        // Determine validated tree key and tree detail setup
        $selectedTreeKey = $this->treeSetup[$queryParams['tree']] ? $queryParams['tree']
            : ($this->treeSetup[$moduleState['tree']] ? $moduleState['tree'] : key($this->treeSetup));
        $selectedTreeDetails = $this->treeSetup[$selectedTreeKey];
        $moduleState['tree'] = $selectedTreeKey;

        // Search string given or regex search enabled?
        $searchString = (string)($postValues['searchString'] ? trim($postValues['searchString']) : '');
        $moduleState['regexSearch'] = (bool)($postValues['regexSearch'] ?? $moduleState['regexSearch'] ?? false);

        // Prepare main array
        $sortKeysByName = true;
        if ($selectedTreeDetails['type'] === 'global') {
            $globalArrayKey = $selectedTreeDetails['globalKey'];
            $renderArray = $GLOBALS[$globalArrayKey];

            // Hook for Processing blindedConfigurationOptions
            $blindedConfigurationOptions = $this->blindedConfigurationOptions;

            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class]['modifyBlindedConfigurationOptions'] ?? [] as $classReference) {
                $processingObject = GeneralUtility::makeInstance($classReference);
                $blindedConfigurationOptions = $processingObject->modifyBlindedConfigurationOptions($blindedConfigurationOptions, $this);
            }

            if (isset($blindedConfigurationOptions[$globalArrayKey])) {
                // Prepare blinding for all database connection types
                foreach (array_keys($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']) as $connectionName) {
                    if ($connectionName !== 'Default') {
                        $blindedConfigurationOptions['TYPO3_CONF_VARS']['DB']['Connections'][$connectionName] =
                            $blindedConfigurationOptions['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
                    }
                }
                ArrayUtility::mergeRecursiveWithOverrule(
                    $renderArray,
                    ArrayUtility::intersectRecursive($blindedConfigurationOptions[$globalArrayKey], $renderArray)
                );
            }
        } elseif ($selectedTreeDetails['type'] === 'beUserTsConfig') {
            $renderArray = $backendUser->getTSConfig();
        } elseif ($selectedTreeDetails['type'] === 'uc') {
            $renderArray = $backendUser->uc;
        } elseif ($selectedTreeDetails['type'] === 'routes') {
            $router = GeneralUtility::makeInstance(Router::class);
            $routes = $router->getRoutes();
            $renderArray = [];
            foreach ($routes as $identifier => $route) {
                /** @var \TYPO3\CMS\Backend\Routing\Route $route */
                $renderArray[$identifier] = [
                    'path' => $route->getPath(),
                    'options' => $route->getOptions()
                ];
            }
        } elseif ($selectedTreeDetails['type'] === 'httpMiddlewareStacks') {
            // Keep the order of the keys
            $sortKeysByName = false;
            $renderArray = [];
            foreach (['frontend', 'backend'] as $stackName) {
                // reversing the array allows the admin to read the stack from top to bottom
                $renderArray[$stackName] = array_reverse($this->container->get($stackName . '.middlewares'));
            }
            $renderArray['raw'] = $this->container->get('middlewares');
        } elseif ($selectedTreeDetails['type'] === 'siteConfiguration') {
            $renderArray = GeneralUtility::makeInstance(SiteTcaConfiguration::class)->getTca();
        } else {
            throw new \RuntimeException('Unknown array type "' . $selectedTreeDetails['type'] . '"', 1507845662);
        }
        if ($sortKeysByName) {
            ArrayUtility::naturalKeySortRecursive($renderArray);
        }

        // Prepare array renderer class, apply search and expand / collapse states
        $arrayBrowser = GeneralUtility::makeInstance(ArrayBrowser::class);
        $arrayBrowser->dontLinkVar = true;
        $arrayBrowser->searchKeysToo = true;
        $arrayBrowser->regexMode = $moduleState['regexSearch'];
        $node = $queryParams['node'];
        if ($searchString) {
            $arrayBrowser->depthKeys = $arrayBrowser->getSearchKeys($renderArray, '', $searchString, []);
        } elseif (is_array($node)) {
            $newExpandCollapse = $arrayBrowser->depthKeys($node, $moduleState['node_' . $selectedTreeKey]);
            $arrayBrowser->depthKeys = $newExpandCollapse;
            $moduleState['node_' . $selectedTreeKey] = $newExpandCollapse;
        } else {
            $arrayBrowser->depthKeys = $moduleState['node_' . $selectedTreeKey] ?? [];
        }

        // Store new state
        $backendUser->uc['moduleData']['system_config'] = $moduleState;
        $backendUser->writeUC();

        // Render main body
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->getRequest()->setControllerExtensionName('lowlevel');
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:lowlevel/Resources/Private/Templates/Backend/Configuration.html'
        ));
        $view->assignMultiple([
            'treeName' => $selectedTreeDetails['label'],
            'searchString' => $searchString,
            'regexSearch' => $moduleState['regexSearch'],
            'tree' => $arrayBrowser->tree($renderArray, ''),
        ]);

        // Prepare module setup
        $moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $moduleTemplate->setContent($view->render());
        $moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Lowlevel/ConfigurationView');

        // Shortcut in doc header
        $shortcutButton = $moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeShortcutButton();
        $shortcutButton->setModuleName('system_config')
            ->setDisplayName($languageService->sL(
                'LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:' . $selectedTreeDetails['label']
            ))
            ->setSetVariables(['tree']);
        $moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($shortcutButton);

        // Main drop down in doc header
        $menu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('tree');
        foreach ($this->treeSetup as $treeKey => $treeDetails) {
            $menuItem = $menu->makeMenuItem();
            /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
            $menuItem->setHref((string)$uriBuilder->buildUriFromRoute('system_config', ['tree' => $treeKey]))
                ->setTitle($languageService->sL(
                    'LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:' . $treeDetails['label']
                ));
            if ($selectedTreeKey === $treeKey) {
                $menuItem->setActive(true);
            }
            $menu->addMenuItem($menuItem);
        }
        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);

        return new HtmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Returns the Backend User
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
