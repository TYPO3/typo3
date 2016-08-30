<?php
namespace TYPO3\CMS\Lowlevel\View;

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
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\BaseScriptClass;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Lowlevel\Utility\ArrayBrowser;

/**
 * Script class for the Config module
 */
class ConfigurationView extends BaseScriptClass
{
    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'system_config';

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Blind configurations which should not be visible
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
                'username' => '******'
            ],
            'SYS' => [
                'encryptionKey' => '******'
            ]
        ]
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->getRequest()->setControllerExtensionName('lowlevel');
    }

    /**
     * Initialization
     *
     * @return void
     */
    public function init()
    {
        $this->menuConfig();
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Lowlevel/ConfigurationView');
    }

    /**
     * Menu Configuration
     *
     * @return void
     */
    public function menuConfig()
    {
        // MENU-ITEMS:
        // If array, then it's a selector box menu
        // If empty string it's just a variable, that'll be saved.
        // Values NOT in this array will not be saved in the settings-array for the module.
        $this->MOD_MENU = [
            'function' => [
                0 => LocalizationUtility::translate('typo3ConfVars', 'lowlevel'),
                1 => LocalizationUtility::translate('tca', 'lowlevel'),
                2 => LocalizationUtility::translate('tcaDescr', 'lowlevel'),
                3 => LocalizationUtility::translate('loadedExt', 'lowlevel'),
                4 => LocalizationUtility::translate('t3services', 'lowlevel'),
                5 => LocalizationUtility::translate('tbemodules', 'lowlevel'),
                6 => LocalizationUtility::translate('tbemodulesext', 'lowlevel'),
                7 => LocalizationUtility::translate('tbeStyles', 'lowlevel'),
                8 => LocalizationUtility::translate('beUser', 'lowlevel'),
                9 => LocalizationUtility::translate('usersettings', 'lowlevel'),
                10 => LocalizationUtility::translate('routes', 'lowlevel')
            ],
            'regexsearch' => '',
            'fixedLgd' => ''
        ];
        // CLEANSE SETTINGS
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->moduleName);
    }

    /**
     * Main function
     *
     * @return void
     */
    public function main()
    {
        /** @var ArrayBrowser $arrayBrowser */
        $arrayBrowser = GeneralUtility::makeInstance(ArrayBrowser::class);
        $label = $this->MOD_MENU['function'][$this->MOD_SETTINGS['function']];
        $search_field = GeneralUtility::_GP('search_field');

        $templatePathAndFilename = GeneralUtility::getFileAbsFileName('EXT:lowlevel/Resources/Private/Templates/Backend/Configuration.html');
        $this->view->setTemplatePathAndFilename($templatePathAndFilename);
        $this->view->assign('label', $label);
        $this->view->assign('search_field', $search_field);
        $this->view->assign('checkbox_checkRegexsearch', BackendUtility::getFuncCheck(0, 'SET[regexsearch]', $this->MOD_SETTINGS['regexsearch'], '', '', 'id="checkRegexsearch"'));

        switch ($this->MOD_SETTINGS['function']) {
            case 0:
                $theVar = $GLOBALS['TYPO3_CONF_VARS'];
                ArrayUtility::naturalKeySortRecursive($theVar);
                $arrayBrowser->varName = '$TYPO3_CONF_VARS';
                break;
            case 1:
                $theVar = $GLOBALS['TCA'];
                ArrayUtility::naturalKeySortRecursive($theVar);
                $arrayBrowser->varName = '$TCA';
                break;
            case 2:
                $theVar = $GLOBALS['TCA_DESCR'];
                ArrayUtility::naturalKeySortRecursive($theVar);
                $arrayBrowser->varName = '$TCA_DESCR';
                break;
            case 3:
                $theVar = $GLOBALS['TYPO3_LOADED_EXT'];
                ArrayUtility::naturalKeySortRecursive($theVar);
                $arrayBrowser->varName = '$TYPO3_LOADED_EXT';
                break;
            case 4:
                $theVar = $GLOBALS['T3_SERVICES'];
                ArrayUtility::naturalKeySortRecursive($theVar);
                $arrayBrowser->varName = '$T3_SERVICES';
                break;
            case 5:
                $theVar = $GLOBALS['TBE_MODULES'];
                ArrayUtility::naturalKeySortRecursive($theVar);
                $arrayBrowser->varName = '$TBE_MODULES';
                break;
            case 6:
                $theVar = $GLOBALS['TBE_MODULES_EXT'];
                ArrayUtility::naturalKeySortRecursive($theVar);
                $arrayBrowser->varName = '$TBE_MODULES_EXT';
                break;
            case 7:
                $theVar = $GLOBALS['TBE_STYLES'];
                ArrayUtility::naturalKeySortRecursive($theVar);
                $arrayBrowser->varName = '$TBE_STYLES';
                break;
            case 8:
                $theVar = $GLOBALS['BE_USER']->uc;
                ArrayUtility::naturalKeySortRecursive($theVar);
                $arrayBrowser->varName = '$BE_USER->uc';
                break;
            case 9:
                $theVar = $GLOBALS['TYPO3_USER_SETTINGS'];
                ArrayUtility::naturalKeySortRecursive($theVar);
                $arrayBrowser->varName = '$TYPO3_USER_SETTINGS';
                break;
            case 10:
                $router = GeneralUtility::makeInstance(Router::class);
                $routes = $router->getRoutes();
                $theVar = [];
                foreach ($routes as $identifier => $route) {
                    $theVar[$identifier] = [
                        'path' => $route->getPath(),
                        'options' => $route->getOptions()
                    ];
                }
                ArrayUtility::naturalKeySortRecursive($theVar);
                $arrayBrowser->varName = 'BackendRoutes';
                break;
            default:
                $theVar = [];
        }
        // Update node:
        $update = 0;
        $node = GeneralUtility::_GET('node');
        // If any plus-signs were clicked, it's registered.
        if (is_array($node)) {
            $this->MOD_SETTINGS['node_' . $this->MOD_SETTINGS['function']] = $arrayBrowser->depthKeys($node, $this->MOD_SETTINGS['node_' . $this->MOD_SETTINGS['function']]);
            $update = 1;
        }
        if ($update) {
            $this->getBackendUser()->pushModuleData($this->moduleName, $this->MOD_SETTINGS);
        }
        $arrayBrowser->dontLinkVar = true;
        $arrayBrowser->depthKeys = $this->MOD_SETTINGS['node_' . $this->MOD_SETTINGS['function']];
        $arrayBrowser->regexMode = $this->MOD_SETTINGS['regexsearch'];
        $arrayBrowser->fixedLgd = $this->MOD_SETTINGS['fixedLgd'];
        $arrayBrowser->searchKeysToo = true;

        // If any POST-vars are send, update the condition array
        if (GeneralUtility::_POST('search') && trim($search_field)) {
            $arrayBrowser->depthKeys = $arrayBrowser->getSearchKeys($theVar, '', $search_field, []);
        }

        // mask sensitive information
        $varName = trim($arrayBrowser->varName, '$');
        if (isset($this->blindedConfigurationOptions[$varName])) {
            ArrayUtility::mergeRecursiveWithOverrule($theVar, $this->blindedConfigurationOptions[$varName]);
        }
        $tree = $arrayBrowser->tree($theVar, '', '');
        $this->view->assign('tree', $tree);

        // Setting up the shortcut button for docheader
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        // Shortcut
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($this->moduleName)
            ->setDisplayName($this->MOD_MENU['function'][$this->MOD_SETTINGS['function']])
            ->setSetVariables(['function']);
        $buttonBar->addButton($shortcutButton);

        $this->getModuleMenu();

        $this->content = '<form action="" id="ConfigurationView" method="post">';
        $this->content .= $this->view->render();
        $this->content .= '</form>';
    }

    /**
     * Injects the request object for the current request or subrequest
     * Simply calls main() and init() and outputs the content
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();
        $this->main();

        $this->moduleTemplate->setContent($this->content);
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Print output to browser
     *
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function printContent()
    {
        GeneralUtility::logDeprecatedFunction();
        echo $this->content;
    }

    /**
     * Generates the action menu
     */
    protected function getModuleMenu()
    {
        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('ConfigurationJumpMenu');

        foreach ($this->MOD_MENU['function'] as $controller => $title) {
            $item = $menu
                ->makeMenuItem()
                ->setHref(
                    BackendUtility::getModuleUrl(
                        $this->moduleName,
                        [
                            'id' => $this->id,
                            'SET' => [
                                'function' => $controller
                            ]
                        ]
                    )
                )
                ->setTitle($title);
            if ($controller === (int)$this->MOD_SETTINGS['function']) {
                $item->setActive(true);
            }
            $menu->addMenuItem($item);
        }
        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }
}
