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

namespace TYPO3\CMS\Backend\Template;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Template\Components\DocHeaderComponent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ResponsableViewInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Fluid\View\BackendTemplateView;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * A class taking care of the "outer" HTML of a module, especially
 * the doc header and other related parts.
 */
final class ModuleTemplate implements ViewInterface, ResponsableViewInterface
{
    use PageRendererBackendSetupTrait;

    protected bool $uiBlock = false;

    protected string $moduleId = '';
    protected string $moduleName = '';
    protected string $moduleClass = '';
    protected string $title = '';
    protected string $bodyTag = '<body>';
    protected string $formTag = '';

    protected FlashMessageQueue $flashMessageQueue;
    protected DocHeaderComponent $docHeaderComponent;

    /**
     * @todo: mark deprecated together with other legacy handling.
     */
    protected ?StandaloneView $legacyView = null;

    /**
     * Init PageRenderer and properties.
     */
    public function __construct(
        protected readonly PageRenderer $pageRenderer,
        protected readonly IconFactory $iconFactory,
        protected readonly FlashMessageService $flashMessageService,
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly ViewInterface $view,
        protected readonly ResponseFactoryInterface $responseFactory,
        protected readonly StreamFactoryInterface $streamFactory,
        protected readonly ServerRequestInterface $request,
    ) {
        $module = $request->getAttribute('module');
        if ($module instanceof ModuleInterface) {
            $this->setModuleName($module->getIdentifier());
        } else {
            $this->setModuleName($request->getAttribute('route')?->getOption('_identifier') ?? '');
        }
        $this->flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $this->docHeaderComponent = GeneralUtility::makeInstance(DocHeaderComponent::class);
        $this->setUpBasicPageRendererForBackend($pageRenderer, $extensionConfiguration, $request, $this->getLanguageService());
        $this->pageRenderer->loadJavaScriptModule('bootstrap');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/context-help.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/document-header.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/global-event-handler.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/action-dispatcher.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/element/immediate-action-element.js');
    }

    /**
     * Add a variable to the view data collection.
     */
    public function assign(string $key, mixed $value): self
    {
        $this->view->assign($key, $value);
        return $this;
    }

    /**
     * Add multiple variables to the view data collection.
     */
    public function assignMultiple(array $values): self
    {
        $this->view->assignMultiple($values);
        return $this;
    }

    /**
     * Render the module.
     */
    public function render(string $templateFileName = ''): string
    {
        $this->assignMultiple([
            'docHeader' => $this->docHeaderComponent->docHeaderContent(),
            'moduleId' => $this->moduleId,
            'moduleName' => $this->moduleName,
            'moduleClass' => $this->moduleClass,
            'uiBlock' => $this->uiBlock,
            'flashMessageQueueIdentifier' => $this->flashMessageQueue->getIdentifier(),
            'formTag' => $this->formTag,
        ]);
        $this->pageRenderer->addBodyContent($this->bodyTag . $this->view->render($templateFileName));
        $this->pageRenderer->setTitle($this->title);
        $updateSignalDetails = BackendUtility::getUpdateSignalDetails();
        if (!empty($updateSignalDetails['html'])) {
            $this->pageRenderer->addHeaderData(implode("\n", $updateSignalDetails['html']));
        }
        // @deprecated will be removed in TYPO3 v13.0
        if (!empty($updateSignalDetails['script'])) {
            $this->pageRenderer->addJsFooterInlineCode('updateSignals', implode("\n", $updateSignalDetails['script']));
        }
        return $this->pageRenderer->render();
    }

    /**
     * Render the module and create an HTML 200 response from it. This is a
     * lazy shortcut so controllers don't need to take care of this in the backend.
     */
    public function renderResponse(string $templateFileName = ''): ResponseInterface
    {
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($this->streamFactory->createStream($this->render($templateFileName)));
    }

    /**
     * Set to something like '<body id="foo">' when a special body tag is needed.
     */
    public function setBodyTag(string $bodyTag): self
    {
        $this->bodyTag = $bodyTag;
        return $this;
    }

    /**
     * Title string of the module: "My module · Edit view"
     */
    public function setTitle(string $title, string $context = ''): self
    {
        $titleComponents = [$title];
        if ($context !== '') {
            $titleComponents[] = $context;
        }
        $this->title = implode(' · ', $titleComponents);
        return $this;
    }

    /**
     * Get the DocHeader. Can be used in controllers to add custom
     * buttons / menus / ... to the doc header.
     */
    public function getDocHeaderComponent(): DocHeaderComponent
    {
        return $this->docHeaderComponent;
    }

    /**
     * A "<form>" tag encapsulating the entire module, including doc-header.
     */
    public function setForm(string $formTag = ''): self
    {
        $this->formTag = $formTag;
        return $this;
    }

    public function setModuleId(string $moduleId): self
    {
        $this->moduleId = $moduleId;
        $this->registerModuleMenu($moduleId);
        return $this;
    }

    public function setModuleName(string $moduleName): self
    {
        $this->moduleName = $moduleName;
        return $this;
    }

    public function setModuleClass(string $moduleClass): self
    {
        $this->moduleClass = $moduleClass;
        return $this;
    }

    /**
     * Creates a message object and adds it to the FlashMessageQueue.
     * These messages are automatically rendered when the view is rendered.
     */
    public function addFlashMessage(string $messageBody, string $messageTitle = '', int $severity = AbstractMessage::OK, bool $storeInSession = true): self
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $messageBody, $messageTitle, $severity, $storeInSession);
        $this->flashMessageQueue->enqueue($flashMessage);
        return $this;
    }

    /**
     * @todo: Document scenarios where this is useful.
     */
    public function setFlashMessageQueue(FlashMessageQueue $flashMessageQueue): self
    {
        $this->flashMessageQueue = $flashMessageQueue;
        return $this;
    }

    /**
     * UI block is a spinner shown during browser rendering phase of the module,
     * automatically removed when rendering finished. This is done by default,
     * but the UI block can be turned off when needed for whatever reason.
     */
    public function setUiBlock(bool $uiBlock): self
    {
        $this->uiBlock = $uiBlock;
        return $this;
    }

    /**
     * @internal Candidate to deprecate when View refactoring finished.
     * @todo: deprecate. legacy.
     */
    public function getView(): StandaloneView
    {
        $this->initLegacyView();
        return $this->legacyView;
    }

    /**
     * @todo: deprecate. legacy.
     */
    public function setContent(string $content): self
    {
        $this->initLegacyView();
        $this->legacyView->assign('content', $content);
        return $this;
    }

    /**
     * @todo deprecate. legacy.
     */
    public function renderContent(): string
    {
        $this->initLegacyView();
        $this->legacyView->assignMultiple([
            'docHeader' => $this->docHeaderComponent->docHeaderContent(),
            'moduleId' => $this->moduleId,
            'moduleName' => $this->moduleName,
            'moduleClass' => $this->moduleClass,
            'formTag' => $this->formTag,
            'uiBlock' => $this->uiBlock,
            'flashMessageQueueIdentifier' => $this->flashMessageQueue->getIdentifier(),
        ]);
        $this->pageRenderer->addBodyContent($this->bodyTag . $this->legacyView->render('ModuleTemplate/Module.html'));
        $this->pageRenderer->setTitle($this->title);
        $updateSignalDetails = BackendUtility::getUpdateSignalDetails();
        if (!empty($updateSignalDetails['html'])) {
            $this->pageRenderer->addHeaderData(implode("\n", $updateSignalDetails['html']));
        }
        // @deprecated will be removed in TYPO3 v13.0
        if (!empty($updateSignalDetails['script'])) {
            $this->pageRenderer->addJsFooterInlineCode('updateSignals', implode("\n", $updateSignalDetails['script']));
        }
        return $this->pageRenderer->render();
    }

    /**
     * @todo: remove along with legacy view handling.
     */
    protected function initLegacyView(): void
    {
        if ($this->legacyView === null) {
            $this->legacyView = GeneralUtility::makeInstance(StandaloneView::class);
            $this->legacyView->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
            $this->legacyView->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates']);
            $this->legacyView->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
        }
    }

    /**
     * Returns the current body tag.
     * @todo: deprecate. ModuleTemplate should be a data sink only, here.
     */
    public function getBodyTag(): string
    {
        return $this->bodyTag;
    }

    /**
     * Generates the Menu for things like Web->Info
     * @todo: deprecate. unused.
     */
    public function registerModuleMenu(string $moduleMenuIdentifier): self
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
     * @todo: render unused and deprecate.
     */
    public function getDynamicTabMenu(array $menuItems, string $domId, int $defaultTabIndex = 1, bool $collapsible = false, bool $wrapContent = true, bool $storeLastActiveTab = true): string
    {
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/tabs.js');
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
     * @todo: render unused and remove.
     */
    public function header(string $text, bool $inlineEdit = true): string
    {
        return '<h1 ' . ($inlineEdit ? 'class="t3js-title-inlineedit"' : '') . '>' . htmlspecialchars($text) . '</h1>';
    }

    /**
     * @todo: deprecate. ModuleTemplate should be a data sink only, here.
     */
    public function isUiBlock(): bool
    {
        return $this->uiBlock;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
