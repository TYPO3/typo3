<?php

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

namespace TYPO3\CMS\Backend\Template;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Template\Components\DocHeaderComponent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\BackendTemplateView;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * A class taking care of the "outer" HTML of a module, especially
 * the doc header and other related parts.
 */
class ModuleTemplate
{
    use PageRendererBackendSetupTrait;

    /**
     * DocHeaderComponent
     *
     * @var DocHeaderComponent
     */
    protected $docHeaderComponent;

    /**
     * @var bool
     */
    protected $uiBlock = false;

    /**
     * TemplateRootPath
     *
     * @var string[]
     */
    protected $templateRootPaths = ['EXT:backend/Resources/Private/Templates'];

    /**
     * PartialRootPath
     *
     * @var string[]
     */
    protected $partialRootPaths = ['EXT:backend/Resources/Private/Partials'];

    /**
     * LayoutRootPath
     *
     * @var string[]
     */
    protected $layoutRootPaths = ['EXT:backend/Resources/Private/Layouts'];

    /**
     * Template name
     *
     * @var string
     */
    protected $templateFile = 'ModuleTemplate/Module.html';

    /**
     * Fluid Standalone View
     *
     * @var ViewInterface
     */
    protected $view;

    /**
     * Content String
     *
     * @var string
     */
    protected $content = '';

    /**
     * Module ID
     *
     * @var string
     */
    protected $moduleId = '';

    /**
     * Module Name
     *
     * @var string
     */
    protected $moduleName = '';

    /**
     * Module Class
     *
     * @var string
     */
    protected $moduleClass = '';

    /**
     * Title Tag
     *
     * @var string
     */
    protected $title = '';

    /**
     * Body Tag
     *
     * @var string
     */
    protected $bodyTag = '<body>';

    protected PageRenderer $pageRenderer;
    protected IconFactory $iconFactory;
    protected FlashMessageQueue $flashMessageQueue;

    /**
     * Init PageRenderer and property objects.
     */
    public function __construct(
        PageRenderer $pageRenderer,
        IconFactory $iconFactory,
        FlashMessageService $flashMessageService,
        ExtensionConfiguration $extensionConfiguration,
        ServerRequestInterface $request = null,
        ViewInterface $view = null
    ) {
        $this->pageRenderer = $pageRenderer;
        $this->iconFactory = $iconFactory;
        $this->flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        // @todo: Make $request argument non-optional in v12.
        $request = $request ?? $GLOBALS['TYPO3_REQUEST'];

        $currentRoute = $request->getAttribute('route');
        if ($currentRoute instanceof Route) {
            if ($currentRoute->hasOption('module') && $currentRoute->getOption('module')) {
                $moduleConfiguration = $currentRoute->getOption('moduleConfiguration');
                if ($moduleConfiguration['name']) {
                    $this->setModuleName($moduleConfiguration['name']);
                }
            } else {
                $this->setModuleName($currentRoute->getOption('_identifier'));
            }
        }
        if ($view === null) {
            $this->view = GeneralUtility::makeInstance(StandaloneView::class);
            $this->view->setPartialRootPaths($this->partialRootPaths);
            $this->view->setTemplateRootPaths($this->templateRootPaths);
            $this->view->setLayoutRootPaths($this->layoutRootPaths);
            $this->view->setTemplate($this->templateFile);
        } else {
            $this->view = $view;
        }
        $this->docHeaderComponent = GeneralUtility::makeInstance(DocHeaderComponent::class);
        $this->setUpBasicPageRendererForBackend($pageRenderer, $extensionConfiguration, $request, $this->getLanguageService());
        $this->loadJavaScripts();
    }

    /**
     * Returns the current body tag
     *
     * @return string
     */
    public function getBodyTag()
    {
        return $this->bodyTag;
    }

    /**
     * Sets the body tag
     *
     * @param string $bodyTag
     * @return self
     */
    public function setBodyTag($bodyTag): self
    {
        $this->bodyTag = $bodyTag;
        return $this;
    }

    /**
     * Gets the standalone view.
     *
     * @return StandaloneView
     * @internal Candidate to deprecate when View refactoring finished.
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Set content
     *
     * @param string $content Content of the module
     * @return self
     */
    public function setContent($content): self
    {
        $this->view->assign('content', $content);
        return $this;
    }

    /**
     * Set title tag
     *
     * @param string $title
     * @param string $context
     * @return self
     */
    public function setTitle($title, $context = ''): self
    {
        $titleComponents = [
            $title,
        ];
        if ($context !== '') {
            $titleComponents[] = $context;
        }
        $this->title = implode(' · ', $titleComponents);
        return $this;
    }

    /**
     * Loads all necessary Javascript Files
     */
    protected function loadJavaScripts()
    {
        $this->pageRenderer->loadJavaScriptModule('bootstrap');

        if ($this->getBackendUserAuthentication() && !empty($this->getBackendUserAuthentication()->user)) {
            $this->pageRenderer->loadJavaScriptModule('TYPO3/CMS/Backend/ContextHelp.js');
            $this->pageRenderer->loadJavaScriptModule('TYPO3/CMS/Backend/DocumentHeader.js');
        }
        $this->pageRenderer->loadJavaScriptModule('TYPO3/CMS/Backend/GlobalEventHandler.js');
        $this->pageRenderer->loadJavaScriptModule('TYPO3/CMS/Backend/ActionDispatcher.js');
        $this->pageRenderer->loadJavaScriptModule('TYPO3/CMS/Backend/Element/ImmediateActionElement.js');
    }

    /**
     * Get the DocHeader
     *
     * @return DocHeaderComponent
     */
    public function getDocHeaderComponent()
    {
        return $this->docHeaderComponent;
    }

    /**
     * Returns the fully rendered view
     *
     * @return string
     */
    public function renderContent()
    {
        $this->pageRenderer->setTitle($this->title);

        $this->view->assign('docHeader', $this->docHeaderComponent->docHeaderContent());
        if ($this->moduleId) {
            $this->view->assign('moduleId', $this->moduleId);
        }
        if ($this->moduleName) {
            $this->view->assign('moduleName', $this->moduleName);
        }
        if ($this->moduleClass) {
            $this->view->assign('moduleClass', $this->moduleClass);
        }
        $this->view->assign('uiBlock', $this->uiBlock);
        $this->view->assign('flashMessageQueueIdentifier', $this->flashMessageQueue->getIdentifier());
        $this->pageRenderer->addBodyContent($this->bodyTag . $this->view->render());

        $updateSignalDetails = BackendUtility::getUpdateSignalDetails();
        if (!empty($updateSignalDetails['html'])) {
            $this->pageRenderer->addHeaderData(
                implode("\n", $updateSignalDetails['html'])
            );
        }
        // @deprecated will be removed in TYPO3 v13.0
        if (!empty($updateSignalDetails['script'])) {
            $this->pageRenderer->addJsFooterInlineCode(
                'updateSignals',
                implode("\n", $updateSignalDetails['script'])
            );
        }
        return $this->pageRenderer->render();
    }

    /**
     * Set form tag
     *
     * @param string $formTag Form tag to add
     * @return self
     */
    public function setForm($formTag = ''): self
    {
        $this->view->assign('formTag', $formTag);
        return $this;
    }

    /**
     * Sets the ModuleId
     *
     * @param string $moduleId ID of the module
     * @return self
     */
    public function setModuleId($moduleId): self
    {
        $this->moduleId = $moduleId;
        $this->registerModuleMenu($moduleId);
        return $this;
    }

    /**
     * Sets the ModuleName
     *
     * @param string $moduleName Name of the module
     * @return self
     */
    public function setModuleName($moduleName): self
    {
        $this->moduleName = $moduleName;
        return $this;
    }

    /**
     * Sets the ModuleClass
     *
     * @param string $moduleClass Class of the module
     * @return self
     */
    public function setModuleClass($moduleClass): self
    {
        $this->moduleClass = $moduleClass;
        return $this;
    }

    /**
     * Generates the Menu for things like Web->Info
     *
     * @param string $moduleMenuIdentifier
     * @return self
     */
    public function registerModuleMenu($moduleMenuIdentifier): self
    {
        if (isset($GLOBALS['TBE_MODULES_EXT'][$moduleMenuIdentifier])) {
            $menuEntries =
                $GLOBALS['TBE_MODULES_EXT'][$moduleMenuIdentifier]['MOD_MENU']['function'];
            $menu = $this->getDocHeaderComponent()->getMenuRegistry()->makeMenu()->setIdentifier('MOD_FUNC');
            foreach ($menuEntries as $menuEntry) {
                $menuItem = $menu->makeMenuItem()
                    ->setTitle($menuEntry['title'])
                    ->setHref('#');
                $menu->addMenuItem($menuItem);
            }
            $this->docHeaderComponent->getMenuRegistry()->addMenu($menu);
        }
        return $this;
    }

    /**
     * Creates a tab menu where the tabs or collapsible are rendered with bootstrap markup
     *
     * @param array $menuItems Tab elements, each element is an array with "label" and "content"
     * @param string $domId DOM id attribute, will be appended with an iteration number per tab.
     * @param int $defaultTabIndex Default tab to open (for toggle <=0). Value corresponds to integer-array index + 1
     *                             (index zero is "1", index "1" is 2 etc.). A value of zero (or something non-existing
     *                             will result in no default tab open.
     * @param bool $collapsible If set, the tabs are rendered as headers instead over each sheet. Effectively this means
     *                          there is no tab menu, but rather a foldout/fold-in menu.
     * @param bool $wrapContent If set, the content is wrapped in div structure which provides a padding and border
     *                          style. Set this FALSE to get unstyled content pane with fullsize content area.
     * @param bool $storeLastActiveTab If set, the last open tab is stored in local storage and will be re-open again.
     *                                 If you don't need this feature, e.g. for wizards like import/export you can
     *                                 disable this behaviour.
     * @return string
     */
    public function getDynamicTabMenu(array $menuItems, $domId, $defaultTabIndex = 1, $collapsible = false, $wrapContent = true, $storeLastActiveTab = true)
    {
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tabs');
        $view = GeneralUtility::makeInstance(BackendTemplateView::class);
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates']);
        $view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $view->assignMultiple([
            'id' => 'DTM-' . md5($domId),
            'items' => $menuItems,
            'defaultTabIndex' => $defaultTabIndex,
            'wrapContent' => $wrapContent,
            'storeLastActiveTab' => $storeLastActiveTab,
        ]);
        return $view->render($collapsible ? 'ModuleTemplate/Collapse' : 'ModuleTemplate/Tabs');
    }

    /**
     * Returns the header-bar in the top of most backend modules
     * Closes section if open.
     *
     * @param string $text The text string for the header
     * @param bool $inlineEdit Whether the header should be editable (e.g. page title)
     * @return string HTML content
     * @internal
     */
    public function header(string $text, bool $inlineEdit = true)
    {
        return '

	<!-- MAIN Header in page top -->
	<h1 ' . ($inlineEdit ? 'class="t3js-title-inlineedit"' : '') . '>' . htmlspecialchars($text) . '</h1>
';
    }

    /**
     * Creates a Message object and adds it to the FlashMessageQueue.
     *
     * @param string $messageBody The message
     * @param string $messageTitle Optional message title
     * @param int $severity Optional severity, must be one of \TYPO3\CMS\Core\Messaging\FlashMessage constants
     * @param bool $storeInSession Optional, defines whether the message should be stored in the session (default)
     * @throws \InvalidArgumentException if the message body is no string
     * @return self
     */
    public function addFlashMessage($messageBody, $messageTitle = '', $severity = AbstractMessage::OK, $storeInSession = true): self
    {
        if (!is_string($messageBody)) {
            throw new \InvalidArgumentException('The message body must be of type string, "' . gettype($messageBody) . '" given.', 1446483133);
        }
        /* @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $messageBody,
            $messageTitle,
            $severity,
            $storeInSession
        );
        $this->flashMessageQueue->enqueue($flashMessage);
        return $this;
    }

    /**
     * @param FlashMessageQueue $flashMessageQueue
     * @return self
     */
    public function setFlashMessageQueue($flashMessageQueue): self
    {
        $this->flashMessageQueue = $flashMessageQueue;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUiBlock(): bool
    {
        return $this->uiBlock;
    }

    /**
     * @param bool $uiBlock
     * @return self
     */
    public function setUiBlock(bool $uiBlock): self
    {
        $this->uiBlock = $uiBlock;
        return $this;
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
