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

namespace TYPO3\CMS\Recycler\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Backend Module for the 'recycler' extension.
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class RecyclerModuleController
{

    /**
     * @var array
     */
    protected $pageRecord = [];

    /**
     * @var bool
     */
    protected $isAccessibleForCurrentUser = false;

    /**
     * @var bool
     */
    protected $allowDelete = false;

    /**
     * @var int
     */
    protected $recordsPageLimit = 50;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        IconFactory $iconFactory,
        PageRenderer $pageRenderer,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Injects the request object for the current request, and renders correct action
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->id = (int)($request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0);
        $backendUser = $this->getBackendUser();
        $this->pageRecord = BackendUtility::readPageAccess($this->id, $backendUser->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];
        $this->isAccessibleForCurrentUser = ($this->id && $this->pageRecord !== []) || (!$this->id && $this->getBackendUser()->isAdmin());
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);

        // don't access in workspace
        if ($backendUser->workspace !== 0) {
            $this->isAccessibleForCurrentUser = false;
        }

        // read configuration
        if ($backendUser->isAdmin()) {
            $this->allowDelete = true;
        } else {
            $this->allowDelete = (bool)($backendUser->getTSConfig()['mod.']['recycler.']['allowDelete'] ?? false);
        }

        $this->recordsPageLimit = MathUtility::forceIntegerInRange(
            (int)($backendUser->getTSConfig()['mod.']['recycler.']['recordsPageLimit'] ?? 25),
            1
        );

        $action = 'index';
        $this->initializeView($action);

        $result = $this->{$action . 'Action'}($request);
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        $this->registerDocHeaderButtons($request->getQueryParams()['route']);

        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * @param string $templateName
     */
    protected function initializeView(string $templateName)
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplate($templateName);
        $this->view->setTemplateRootPaths(['EXT:recycler/Resources/Private/Templates/RecyclerModule']);
        $this->view->setPartialRootPaths(['EXT:recycler/Resources/Private/Partials']);
        $this->view->setLayoutRootPaths(['EXT:recycler/Resources/Private/Layouts']);
        $this->view->getRequest()->setControllerExtensionName('Recycler');
    }

    /**
     * Renders the content of the module.
     *
     * @param ServerRequestInterface $request
     */
    public function indexAction(ServerRequestInterface $request)
    {
        $this->pageRenderer->addInlineSettingArray('Recycler', $this->getJavaScriptConfiguration($request->getAttribute('normalizedParams')));
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:recycler/Resources/Private/Language/locallang.xlf');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/MultiRecordSelection');
        if ($this->isAccessibleForCurrentUser) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageRecord);
        }

        $this->moduleTemplate->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:recycler/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            $this->pageRecord['title'] ?? ''
        );

        $this->view->assign('allowDelete', $this->allowDelete);
    }

    /**
     * Registers the Icons into the docheader
     *
     * @param string $route
     * @throws \InvalidArgumentException
     */
    protected function registerDocHeaderButtons(string $route)
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('web_RecyclerRecycler')
            ->setDisplayName($this->getShortcutTitle())
            ->setArguments(['id' => (int)$this->id]);
        $buttonBar->addButton($shortcutButton);

        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setDataAttributes(['action' => 'reload'])
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:recycler/Resources/Private/Language/locallang.xlf:button.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Gets the JavaScript configuration.
     *
     * @param NormalizedParams $normalizedParams
     * @return array The JavaScript configuration
     */
    protected function getJavaScriptConfiguration(NormalizedParams $normalizedParams): array
    {
        return [
            'pagingSize' => $this->recordsPageLimit,
            'showDepthMenu' => true,
            'startUid' => $this->id,
            'isSSL' => $normalizedParams->isHttps(),
            'deleteDisable' => !$this->allowDelete,
            'depthSelection' => $this->getDataFromSession('depthSelection', '0'),
            'tableSelection' => $this->getDataFromSession('tableSelection', ''),
            'States' => $this->getBackendUser()->uc['moduleData']['web_recycler']['States'] ?? [],
        ];
    }

    /**
     * Gets data from the session of the current backend user.
     *
     * @param string $identifier The identifier to be used to get the data
     * @param string $default The default date to be used if nothing was found in the session
     * @return string The accordant data in the session of the current backend user
     */
    protected function getDataFromSession($identifier, $default = null)
    {
        $sessionData = &$this->getBackendUser()->uc['tx_recycler'];
        if (isset($sessionData[$identifier]) && $sessionData[$identifier]) {
            $data = $sessionData[$identifier];
        } else {
            $data = $default;
        }
        return $data;
    }

    /**
     * Returns the shortcut title for the current page
     *
     * @return string
     */
    protected function getShortcutTitle(): string
    {
        return sprintf(
            '%s: %s [%d]',
            $this->getLanguageService()->sL('LLL:EXT:recycler/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            BackendUtility::getRecordTitle('pages', $this->pageRecord),
            $this->id
        );
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
