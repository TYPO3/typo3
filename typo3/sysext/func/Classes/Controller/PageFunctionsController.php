<?php
namespace TYPO3\CMS\Func\Controller;

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
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;

/**
 * Script Class for the Web > Functions module
 * This class creates the framework to which other extensions can connect their sub-modules
 */
class PageFunctionsController extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{
    /**
     * @var array
     * @internal
     */
    public $pageinfo;

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Document Template Object
     *
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'web_func';

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_mod_web_func.xlf');
        $this->MCONF = array(
            'name' => $this->moduleName,
        );
    }

    /**
     * Injects the request object for the current request or subrequest
     * Then checks for module functions that have hooked in, and renders menu etc.
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();

        // Checking for first level external objects
        $this->checkExtObj();

        // Checking second level external objects
        $this->checkSubExtObj();
        $this->main();

        $this->moduleTemplate->setContent($this->content);

        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Initialize module header etc and call extObjContent function
     *
     * @return void
     */
    public function main()
    {
        // Access check...
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        if ($this->pageinfo) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
        }
        $access = is_array($this->pageinfo);
        // We keep this here, in case somebody relies on the old doc being here
        $this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
        // Main
        if ($this->id && $access) {
            // JavaScript
            $this->moduleTemplate->addJavaScriptCode(
                'WebFuncInLineJS',
                'if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';');
            // Setting up the context sensitive menu:
            $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');

            $this->view = $this->getFluidTemplateObject('func', 'func');
            $this->view->assign('moduleName', BackendUtility::getModuleUrl('web_func'));
            $this->view->assign('id', $this->id);
            $this->view->assign('versionSelector', $this->moduleTemplate->getVersionSelector($this->id, true));
            $this->view->assign('functionMenuModuleContent', $this->getExtObjContent());
            // Setting up the buttons and markers for docheader
            $this->getButtons();
            $this->generateMenu();
            $this->content .= $this->view->render();
        } else {
            // If no access or if ID == zero
            $title = $this->getLanguageService()->getLL('title');
            $message = $this->getLanguageService()->getLL('clickAPage_content');
            $this->view = $this->getFluidTemplateObject('func', 'func', 'InfoBox');
            $this->view->assignMultiple(array(
                'title' => $title,
                'message' => $message,
                'state' => InfoboxViewHelper::STATE_INFO
            ));
            $this->content = $this->view->render();
            // Setting up the buttons and markers for docheader
            $this->getButtons();
        }
    }

    /**
     * Generates the menu based on $this->MOD_MENU
     *
     * @throws \InvalidArgumentException
     */
    protected function generateMenu()
    {
        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('WebFuncJumpMenu');
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
            if ($controller === $this->MOD_SETTINGS['function']) {
                $item->setActive(true);
            }
            $menu->addMenuItem($item);
        }
        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        // CSH
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('_MOD_web_func')
            ->setFieldName('');
        $buttonBar->addButton($cshButton);
        if ($this->id && is_array($this->pageinfo)) {
            // View page
            $viewButton = $buttonBar->makeLinkButton()
                ->setOnClick(BackendUtility::viewOnClick($this->pageinfo['uid'], '', BackendUtility::BEgetRootLine($this->pageinfo['uid'])))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage'))
                ->setIcon($this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL))
                ->setHref('#');
            $buttonBar->addButton($viewButton);
            // Shortcut
            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setModuleName($this->moduleName)
                ->setGetVariables(['id', 'edit_record', 'pointer', 'new_unique_uid', 'search_field', 'search_levels', 'showLimit'])
                ->setSetVariables(array_keys($this->MOD_MENU));
            $buttonBar->addButton($shortcutButton);
        }
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
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * returns a new standalone view, shorthand function
     *
     * @param string $extensionName
     * @param string $controllerExtensionname
     * @param string $templateName
     * @return StandaloneView
     */
    protected function getFluidTemplateObject($extensionName, $controllerExtensionName, $templateName = 'Main')
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(array(GeneralUtility::getFileAbsFileName('EXT:' . $extensionName . '/Resources/Private/Layouts')));
        $view->setPartialRootPaths(array(GeneralUtility::getFileAbsFileName('EXT:' . $extensionName . '/Resources/Private/Partials')));
        $view->setTemplateRootPaths(array(GeneralUtility::getFileAbsFileName('EXT:' . $extensionName . '/Resources/Private/Templates')));

        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:' . $extensionName . '/Resources/Private/Templates/' . $templateName . '.html'));

        $view->getRequest()->setControllerExtensionName($controllerExtensionName);
        return $view;
    }
}
