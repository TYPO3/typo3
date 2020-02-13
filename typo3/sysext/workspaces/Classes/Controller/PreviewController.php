<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Workspaces\Controller;

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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Information\Typo3Information;
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
     * @var StagesService
     */
    protected $stageService;

    /**
     * @var WorkspaceService
     */
    protected $workspaceService;

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

    /**
     * Set up the module template
     */
    public function __construct()
    {
        $this->stageService = GeneralUtility::makeInstance(StagesService::class);
        $this->workspaceService = GeneralUtility::makeInstance(WorkspaceService::class);
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Workspaces/Preview');
        $this->moduleTemplate->getDocHeaderComponent()->disable();
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $states = $this->getBackendUser()->uc['moduleData']['Workspaces']['States'];
        $this->moduleTemplate->getPageRenderer()->addInlineSetting('Workspaces', 'States', $states);
        $this->moduleTemplate->getPageRenderer()->addInlineSetting('FormEngine', 'moduleUrl', (string)$uriBuilder->buildUriFromRoute('record_edit'));
        $this->moduleTemplate->getPageRenderer()->addInlineSetting('RecordHistory', 'moduleUrl', (string)$uriBuilder->buildUriFromRoute('record_history'));
        $this->moduleTemplate->getPageRenderer()->addJsInlineCode('workspace-inline-code', $this->generateJavascript());
        $this->moduleTemplate->getPageRenderer()->addCssFile('EXT:workspaces/Resources/Public/Css/preview.css');
        $this->moduleTemplate->getPageRenderer()->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/wizard.xlf');
        $this->moduleTemplate->getPageRenderer()->addInlineLanguageLabelFile('EXT:workspaces/Resources/Private/Language/locallang.xlf');
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
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
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
            $filter = 1,
            $stage = -99,
            $this->pageId,
            $recursionLevel = 0,
            $selectionType = 'tables_modify'
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

        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        try {
            $site = $siteFinder->getSiteByPageId($this->pageId);
            if (isset($queryParameters['L'])) {
                $queryParameters['_language'] = $site->getLanguageById((int)$queryParameters['L']);
                unset($queryParameters['L']);
            }
            $parameters = $queryParameters;
            if (!WorkspaceService::isNewPage($this->pageId)) {
                $parameters['ADMCMD_noBeUser'] = 1;
                $parameters['ADMCMD_prev'] = 'IGNORE';
                $liveUrl = (string)$site->getRouter()->generateUri($this->pageId, $parameters);
            }

            $parameters = $queryParameters;
            $parameters['ADMCMD_prev'] = 'IGNORE';
            $wsUrl = (string)$site->getRouter()->generateUri($this->pageId, $parameters);
        } catch (SiteNotFoundException | InvalidRouteArgumentsException $e) {
            throw new UnableToLinkToPageException('The page ' . $this->pageId . ' had no proper connection to a site, no link could be built.', 1559794913);
        }

        // Build the "list view" link to the review controller
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $wsSettingsUrl = $uriBuilder->buildUriFromRoute('web_WorkspacesWorkspaces', [
            'tx_workspaces_web_workspacesworkspaces' => ['action' => 'singleIndex'],
            'id' => $this->pageId
        ], UriBuilder::ABSOLUTE_URL);

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
        $this->moduleTemplate->getPageRenderer()->addJsFile('EXT:backend/Resources/Public/JavaScript/backend.js');
        $this->moduleTemplate->getPageRenderer()->addInlineSetting('Workspaces', 'SplitPreviewModes', $splitPreviewModes);
        $this->moduleTemplate->getPageRenderer()->addInlineSetting('Workspaces', 'id', $this->pageId);

        $this->view->assignMultiple([
            'logoLink' => Typo3Information::URL_COMMUNITY,
            'liveUrl' => $liveUrl ?? false,
            'wsUrl' => $wsUrl,
            'wsSettingsUrl' => $wsSettingsUrl,
            'activeWorkspace' => $availableWorkspaces[$activeWorkspace],
            'splitPreviewModes' => $splitPreviewModes,
            'firstPreviewMode' => current($splitPreviewModes),
            'enablePreviousStageButton' => $this->isValidStage($previousStage),
            'enableNextStageButton' => $this->isValidStage($nextStage),
            'enableDiscardStageButton' => $this->isValidStage($nextStage) || $this->isValidStage($previousStage),
            'nextStage' => $nextStage['title'],
            'nextStageId' => $nextStage['uid'],
            'prevStage' => $previousStage['title'],
            'prevStageId' => $previousStage['uid'],
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
     * Generates the JavaScript code for the backend,
     * and since we're loading a backend module outside of the actual backend
     * this copies parts of the backend main script.
     *
     * @return string
     */
    protected function generateJavascript(): string
    {
        // Needed for FormEngine manipulation (date picker)
        $dateFormat = ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? ['MM-DD-YYYY', 'HH:mm MM-DD-YYYY'] : ['DD-MM-YYYY', 'HH:mm DD-MM-YYYY']);
        $this->moduleTemplate->getPageRenderer()->addInlineSetting('DateTimePicker', 'DateFormat', $dateFormat);

        // If another page module was specified, replace the default Page module with the new one
        $pageModule = \trim($this->getBackendUser()->getTSConfig()['options.']['overridePageModule'] ?? '');
        $pageModule = BackendUtility::isModuleSetInTBE_MODULES($pageModule) ? $pageModule : 'web_layout';
        $pageModuleUrl = '';
        if (!$this->getBackendUser()->check('modules', $pageModule)) {
            $pageModule = '';
        } else {
            $pageModuleUrl = (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute($pageModule);
        }
        $t3Configuration = [
            'username' => htmlspecialchars($this->getBackendUser()->user['username']),
            'pageModule' => $pageModule,
            'pageModuleUrl' => $pageModuleUrl,
            'inWorkspace' => $this->getBackendUser()->workspace !== 0,
            'showRefreshLoginPopup' => (bool)($GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup'] ?? false)
        ];

        return 'TYPO3.configuration = ' . json_encode($t3Configuration) . ';';
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
