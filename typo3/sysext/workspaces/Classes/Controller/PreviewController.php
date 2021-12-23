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

namespace TYPO3\CMS\Workspaces\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Workspaces\Service\StagesService;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Implements the preview controller of the workspace module.
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class PreviewController
{
    /**
     * @var int
     */
    protected $pageId;

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var ViewInterface
     */
    protected $view;

    protected StagesService $stageService;
    protected WorkspaceService $workspaceService;
    protected PageRenderer $pageRenderer;
    protected UriBuilder $uriBuilder;
    protected SiteFinder $siteFinder;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        StagesService $stageService,
        WorkspaceService $workspaceService,
        PageRenderer $pageRenderer,
        UriBuilder $uriBuilder,
        SiteFinder $siteFinder,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->stageService = $stageService;
        $this->workspaceService = $workspaceService;
        $this->pageRenderer = $pageRenderer;
        $this->uriBuilder = $uriBuilder;
        $this->siteFinder = $siteFinder;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Sets up the view
     *
     * @param string $templateName
     */
    protected function initializeView(string $templateName)
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplate($templateName);
        $this->view->setTemplateRootPaths(['EXT:workspaces/Resources/Private/Templates/Preview']);
        $this->view->setPartialRootPaths(['EXT:workspaces/Resources/Private/Partials']);
        $this->view->setLayoutRootPaths(['EXT:workspaces/Resources/Private/Layouts']);
    }

    /**
     * Basically makes sure that the workspace preview is rendered.
     * The preview itself consists of three frames, so there are
     * only the frames-urls we have to generate here
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->moduleTemplate->getDocHeaderComponent()->disable();
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Workspaces/Preview');
        $this->pageRenderer->addInlineSetting('Workspaces', 'States', $this->getBackendUser()->uc['moduleData']['Workspaces']['States'] ?? []);
        $this->pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_edit'));
        $this->pageRenderer->addInlineSetting('RecordHistory', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_history'));
        // Needed for FormEngine manipulation (date picker)
        $this->pageRenderer->addInlineSetting(
            'DateTimePicker',
            'DateFormat',
            ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ?? false)
                ? ['MM-DD-Y', 'HH:mm MM-DD-Y']
                : ['DD-MM-Y', 'HH:mm DD-MM-Y']
        );
        // @todo Most likely the inline configuration can be removed. Seems to be unused in the JavaScript module
        $this->pageRenderer->addInlineSetting('TYPO3', 'configuration', [
            'username' => htmlspecialchars($this->getBackendUser()->user['username']),
            'showRefreshLoginPopup' => (bool)($GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup'] ?? false),
        ]);
        $this->pageRenderer->addCssFile('EXT:workspaces/Resources/Public/Css/preview.css');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/wizard.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:workspaces/Resources/Private/Language/locallang.xlf');

        $liveUrl = false;
        $this->initializeView('Index');

        // Get all the GET parameters to pass them on to the frames
        $queryParameters = $request->getQueryParams();

        $previewWS = $queryParameters['previewWS'] ?? null;
        $this->pageId = (int)$queryParameters['id'];

        // Remove the GET parameters related to the workspaces module
        unset($queryParameters['route'], $queryParameters['token'], $queryParameters['previewWS']);

        // fetch the next and previous stage
        $workspaceItemsArray = $this->workspaceService->selectVersionsInWorkspace(
            $this->stageService->getWorkspaceId(),
            -99,
            $this->pageId,
            0,
            'tables_modify'
        );
        [, $nextStage] = $this->stageService->getNextStageForElementCollection($workspaceItemsArray);
        [, $previousStage] = $this->stageService->getPreviousStageForElementCollection($workspaceItemsArray);
        $availableWorkspaces = $this->workspaceService->getAvailableWorkspaces();
        $activeWorkspace = $this->getBackendUser()->workspace;
        if ($previewWS !== null && array_key_exists($previewWS, $availableWorkspaces) && $activeWorkspace != $previewWS) {
            $activeWorkspace = $previewWS;
            $this->getBackendUser()->setWorkspace($activeWorkspace);
            BackendUtility::setUpdateSignal('updatePageTree');
        }

        try {
            $site = $this->siteFinder->getSiteByPageId($this->pageId);
            if (isset($queryParameters['L'])) {
                $queryParameters['_language'] = $site->getLanguageById((int)$queryParameters['L']);
                unset($queryParameters['L']);
            }
            $parameters = $queryParameters;
            if (!WorkspaceService::isNewPage($this->pageId)) {
                $parameters['ADMCMD_prev'] = 'LIVE';
                $liveUrl = (string)$site->getRouter()->generateUri($this->pageId, $parameters);
            }

            $parameters = $queryParameters;
            $parameters['ADMCMD_prev'] = 'IGNORE';
            $wsUrl = (string)$site->getRouter()->generateUri($this->pageId, $parameters);
        } catch (SiteNotFoundException | InvalidRouteArgumentsException $e) {
            throw new UnableToLinkToPageException(sprintf('The link to the page with ID "%d" could not be generated: %s', $this->pageId, $e->getMessage()), 1559794913, $e);
        }

        // Evaluate available preview modes
        $splitPreviewModes = GeneralUtility::trimExplode(
            ',',
            BackendUtility::getPagesTSconfig($this->pageId)['workspaces.']['splitPreviewModes'] ?? '',
            true
        );
        $allPreviewModes = ['slider', 'vbox', 'hbox'];
        if (!array_intersect($splitPreviewModes, $allPreviewModes)) {
            $splitPreviewModes = $allPreviewModes;
        }
        $this->pageRenderer->addJsFile('EXT:backend/Resources/Public/JavaScript/backend.js');
        $this->pageRenderer->addInlineSetting('Workspaces', 'SplitPreviewModes', $splitPreviewModes);
        $this->pageRenderer->addInlineSetting('Workspaces', 'id', $this->pageId);

        $this->view->assignMultiple([
            'logoLink' => Typo3Information::URL_COMMUNITY,
            'liveUrl' => $liveUrl ?? false,
            'wsUrl' => $wsUrl,
            'activeWorkspace' => $availableWorkspaces[$activeWorkspace],
            'splitPreviewModes' => $splitPreviewModes,
            'firstPreviewMode' => current($splitPreviewModes),
            'enablePreviousStageButton' => $this->isValidStage($previousStage),
            'enableNextStageButton' => $this->isValidStage($nextStage),
            'enableDiscardStageButton' => $this->isValidStage($nextStage) || $this->isValidStage($previousStage),
            'nextStage' => $nextStage['title'] ?? '',
            'nextStageId' => $nextStage['uid'] ?? 0,
            'prevStage' => $previousStage['title'] ?? '',
            'prevStageId' => $previousStage['uid'] ?? 0,
        ]);

        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Evaluate the activate state based on given $stageArray.
     *
     * @param array $stageArray
     * @return bool
     */
    protected function isValidStage($stageArray): bool
    {
        return is_array($stageArray) && !empty($stageArray);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
