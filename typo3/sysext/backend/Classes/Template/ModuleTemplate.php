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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\DocHeaderComponent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ResponsableViewInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\View\TemplateView;

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
     * @deprecated: Remove together with legacy view handling below in v13.
     */
    protected ?StandaloneView $legacyView = null;

    /**
     * Init PageRenderer and properties.
     */
    public function __construct(
        protected readonly PageRenderer $pageRenderer,
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleProvider $moduleProvider,
        protected readonly FlashMessageService $flashMessageService,
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly ViewInterface $view,
        protected readonly ServerRequestInterface $request,
    ) {
        $module = $request->getAttribute('module');
        if ($module instanceof ModuleInterface) {
            // third level, needs the second level in order to keep highlighting in the module menu
            if ($module->getParentModule()?->getParentModule()) {
                $this->setModuleName($module->getParentModule()->getIdentifier());
            } else {
                $this->setModuleName($module->getIdentifier());
            }
            $this->setModuleName($module->getIdentifier());
        } else {
            $this->setModuleName($request->getAttribute('route')?->getOption('_identifier') ?? '');
        }
        $this->flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $this->docHeaderComponent = GeneralUtility::makeInstance(DocHeaderComponent::class);
        $this->setUpBasicPageRendererForBackend($pageRenderer, $extensionConfiguration, $request, $this->getLanguageService());
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
        $this->prepareRender($templateFileName);
        return $this->pageRenderer->render();
    }

    /**
     * Render the module and create an HTML 200 response from it. This is a
     * lazy shortcut so controllers don't need to take care of this in the backend.
     */
    public function renderResponse(string $templateFileName = ''): ResponseInterface
    {
        $this->prepareRender($templateFileName);
        return $this->pageRenderer->renderResponse();
    }

    protected function prepareRender(string $templateFileName): void
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
        $this->pageRenderer->loadJavaScriptModule('bootstrap');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/context-help.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/document-header.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/global-event-handler.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/action-dispatcher.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/element/immediate-action-element.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/live-search/live-search-shortcut.js');
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
        $this->dispatchNotificationMessages();
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

    /**
     * Optional 'data-module-id="{moduleId}"' on first <div> in body.
     * Can be helpful in JavaScript.
     */
    public function setModuleId(string $moduleId): self
    {
        $this->moduleId = $moduleId;
        return $this;
    }

    /**
     * Optional 'data-module-name="{moduleName}"' on first <div> in body.
     * Can be helpful in JavaScript.
     */
    public function setModuleName(string $moduleName): self
    {
        $this->moduleName = $moduleName;
        return $this;
    }

    /**
     * Optional 'class="module {moduleClass}"' on first <div> in body.
     * Can be helpful styling modules.
     */
    public function setModuleClass(string $moduleClass): self
    {
        $this->moduleClass = $moduleClass;
        return $this;
    }

    /**
     * Creates a message object and adds it to the FlashMessageQueue.
     * These messages are automatically rendered when the view is rendered.
     *
     * @todo: Change $severity to allow ContextualFeedbackSeverity only in v13
     */
    public function addFlashMessage(string $messageBody, string $messageTitle = '', int|ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK, bool $storeInSession = true): self
    {
        if (is_int($severity)) {
            // @deprecated int type for $severity deprecated in v12, will change to Severity only in v13.
            $severity = ContextualFeedbackSeverity::transform($severity);
        }
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $messageBody, $messageTitle, $severity, $storeInSession);
        $this->flashMessageQueue->enqueue($flashMessage);
        return $this;
    }

    /**
     * ModuleTemplate by default uses queue 'core.template.flashMessages'. Modules
     * may want to maintain an own queue. Use this method to render flash messages
     * of a non-default queue at the default position in module HTML output. Call
     * this method *before* adding single messages with addFlashMessage().
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
     * @deprecated since v12, will be removed in v13.
     */
    public function getView(): StandaloneView
    {
        trigger_error('Method ' . __METHOD__ . ' has been deprecated in v12 and will be removed with v13.', E_USER_DEPRECATED);
        $this->initLegacyView();
        return $this->legacyView;
    }

    /**
     * @deprecated since v12, will be removed in v13.
     */
    public function setContent(string $content): self
    {
        $this->initLegacyView();
        $this->legacyView->assign('content', $content);
        return $this;
    }

    /**
     * @deprecated since v12, will be removed in v13. Remove together with $legacyView property and Templates/Module.html.
     */
    public function renderContent(): string
    {
        trigger_error(
            'Method ' . __METHOD__ . ' has been deprecated in v12 and will be removed with v13.'
            . ' Use assign(), assignMultiple(), render() or renderResponse() instead and use Module'
            . ' as *layout* in templates.',
            E_USER_DEPRECATED
        );
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
        $this->pageRenderer->loadJavaScriptModule('bootstrap');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/context-help.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/document-header.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/global-event-handler.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/action-dispatcher.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/element/immediate-action-element.js');
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
        $this->dispatchNotificationMessages();
        return $this->pageRenderer->render();
    }

    /**
     * @deprecated since v12, will be removed in v13.
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
     * @deprecated since v12, will be removed in v13.
     */
    public function getBodyTag(): string
    {
        trigger_error('Method ' . __METHOD__ . ' has been deprecated in v12 and will be removed with v13.', E_USER_DEPRECATED);
        return $this->bodyTag;
    }

    /**
     * Generates the Menu for things like Web->Info
     * @deprecated since v12, will be removed in v13.
     */
    public function registerModuleMenu(string $moduleMenuIdentifier): self
    {
        trigger_error('Method ' . __METHOD__ . ' has been deprecated in v12 and will be removed with v13.', E_USER_DEPRECATED);
        return $this;
    }

    /**
     * Generates a menu in the docheader to access third-level modules
     */
    public function makeDocHeaderModuleMenu(array $additionalQueryParams = []): self
    {
        $currentModule = $this->request->getAttribute('module');
        if (!($currentModule instanceof ModuleInterface)) {
            // Early return in case the current request does not provide a module
            return $this;
        }
        if ($currentModule->getParentModule()?->hasParentModule()) {
            $menuModule = $this->moduleProvider->getModuleForMenu($currentModule->getParentIdentifier(), $this->getBackendUser());
        } else {
            // This is a fallback in case a second level module is called here
            $menuModule = $this->moduleProvider->getModuleForMenu($currentModule->getIdentifier(), $this->getBackendUser());
        }
        if ($menuModule === null) {
            return $this;
        }
        $menu = $this->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('moduleMenu');
        foreach ($menuModule->getSubModules() as $module) {
            $item = $menu
                ->makeMenuItem()
                ->setHref(
                    (string)$this->uriBuilder->buildUriFromRoute(
                        $module->getIdentifier(),
                        $additionalQueryParams,
                    )
                )
                ->setTitle($this->getLanguageService()->sL($module->getTitle()));
            if ($module->getIdentifier() === $currentModule->getIdentifier()) {
                $item->setActive(true);
            }
            $menu->addMenuItem($item);
        }
        $this->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
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
     * @deprecated since v12, will be removed in v13.
     */
    public function getDynamicTabMenu(array $menuItems, string $domId, int $defaultTabIndex = 1, bool $collapsible = false, bool $wrapContent = true, bool $storeLastActiveTab = true): string
    {
        trigger_error(
            'Method ' . __METHOD__ . ' has been deprecated in v12 and will be removed with v13.'
            . ' Inline the HTML into your template.',
            E_USER_DEPRECATED
        );
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/tabs.js');
        $view = GeneralUtility::makeInstance(TemplateView::class);
        $templatePaths = $view->getRenderingContext()->getTemplatePaths();
        $templatePaths->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates')]);
        $templatePaths->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
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
     * @deprecated since v12, will be removed in v13.
     */
    public function header(string $text, bool $inlineEdit = true): string
    {
        trigger_error(
            'Method ' . __METHOD__ . ' has been deprecated in v12 and will be removed with v13.'
            . ' Inline the HTML into your template.',
            E_USER_DEPRECATED
        );
        return '<h1 ' . ($inlineEdit ? 'class="t3js-title-inlineedit"' : '') . '>' . htmlspecialchars($text) . '</h1>';
    }

    /**
     * Dispatches all messages in a special FlashMessageQueue to the PageRenderer to be rendered as inline notifications
     */
    protected function dispatchNotificationMessages(): void
    {
        $notificationQueue = $this->flashMessageService->getMessageQueueByIdentifier(FlashMessageQueue::NOTIFICATION_QUEUE);
        foreach ($notificationQueue->getAllMessagesAndFlush() as $message) {
            $notificationInstruction = JavaScriptModuleInstruction::create('@typo3/backend/notification.js');
            $notificationInstruction->invoke('showMessage', $message->getTitle(), $message->getMessage(), $message->getSeverity());
            $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction($notificationInstruction);
        }
    }

    /**
     * @deprecated since v12, will be removed in v13.
     */
    public function isUiBlock(): bool
    {
        trigger_error('Method ' . __METHOD__ . ' has been deprecated in v12 and will be removed with v13.', E_USER_DEPRECATED);
        return $this->uiBlock;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
