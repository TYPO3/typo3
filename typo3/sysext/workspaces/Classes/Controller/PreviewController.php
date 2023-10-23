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
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Service\StagesService;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Implements the preview controller of the workspace module.
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class PreviewController
{
    public function __construct(
        protected readonly StagesService $stageService,
        protected readonly WorkspaceService $workspaceService,
        protected readonly PageRenderer $pageRenderer,
        protected readonly UriBuilder $uriBuilder,
        protected readonly SiteFinder $siteFinder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory
    ) {}

    /**
     * Basically makes sure that the workspace preview is rendered.
     * The preview itself consists of three frames, so there are
     * only the frames-urls we have to generate here.
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $queryParameters = $request->getQueryParams();
        $previewWS = $queryParameters['previewWS'] ?? null;
        $pageUid = (int)$queryParameters['id'];
        $backendUser = $this->getBackendUser();

        // Initialize module template here, so custom css / js is loaded afterwards (making overrides possible)
        $view = $this->moduleTemplateFactory->create($request);
        $view->getDocHeaderComponent()->disable();

        $this->pageRenderer->loadJavaScriptModule('@typo3/workspaces/preview.js');
        $this->pageRenderer->addInlineSetting('Workspaces', 'States', $backendUser->uc['moduleData']['Workspaces']['States'] ?? []);
        $this->pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_edit'));
        $this->pageRenderer->addInlineSetting('RecordHistory', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_history'));
        // Needed for FormEngine manipulation (date picker)
        $dateFormat = ['dd-MM-yyyy', 'HH:mm dd-MM-yyyy'];
        $this->pageRenderer->addInlineSetting('DateTimePicker', 'DateFormat', $dateFormat);
        // @todo Most likely the inline configuration can be removed. Seems to be unused in the JavaScript module
        $this->pageRenderer->addInlineSetting('TYPO3', 'configuration', [
            'username' => htmlspecialchars($backendUser->user['username']),
            'showRefreshLoginPopup' => (bool)($GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup'] ?? false),
        ]);
        $this->pageRenderer->addCssFile('EXT:workspaces/Resources/Public/Css/preview.css');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/wizard.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:workspaces/Resources/Private/Language/locallang.xlf');
        // Evaluate available preview modes
        $splitPreviewModes = GeneralUtility::trimExplode(
            ',',
            BackendUtility::getPagesTSconfig($pageUid)['workspaces.']['splitPreviewModes'] ?? '',
            true
        );
        $allPreviewModes = ['slider', 'vbox', 'hbox'];
        if (!array_intersect($splitPreviewModes, $allPreviewModes)) {
            $splitPreviewModes = $allPreviewModes;
        }
        $this->pageRenderer->addInlineSetting('Workspaces', 'SplitPreviewModes', $splitPreviewModes);
        $this->pageRenderer->addInlineSetting('Workspaces', 'id', $pageUid);

        // Fetch next and previous stage
        $workspaceItemsArray = $this->workspaceService->selectVersionsInWorkspace(
            $this->stageService->getWorkspaceId(),
            -99,
            $pageUid,
            0,
            'tables_modify'
        );
        [, $nextStage] = $this->stageService->getNextStageForElementCollection($workspaceItemsArray);
        [, $previousStage] = $this->stageService->getPreviousStageForElementCollection($workspaceItemsArray);
        $availableWorkspaces = $this->workspaceService->getAvailableWorkspaces();
        $activeWorkspace = $backendUser->workspace;
        if ($previewWS !== null && array_key_exists($previewWS, $availableWorkspaces) && $activeWorkspace != $previewWS) {
            $activeWorkspace = $previewWS;
            $backendUser->setWorkspace($activeWorkspace);
            BackendUtility::setUpdateSignal('updatePageTree');
        }

        try {
            $liveUrl = false;
            $site = $this->siteFinder->getSiteByPageId($pageUid);
            // Remove GET parameters related to the workspaces module
            unset($queryParameters['route'], $queryParameters['token'], $queryParameters['previewWS']);
            if (isset($queryParameters['L'])) {
                $queryParameters['_language'] = $site->getLanguageById((int)$queryParameters['L']);
                unset($queryParameters['L']);
            }
            $parameters = $queryParameters;
            if (!$this->workspaceService->isNewPage($pageUid)) {
                $parameters['ADMCMD_prev'] = 'LIVE';
                $liveUrl = $this->generateUrl($site, $pageUid, $parameters);
            }
            $parameters = $queryParameters;
            $parameters['ADMCMD_prev'] = 'IGNORE';
            $wsUrl = $this->generateUrl($site, $pageUid, $parameters);
        } catch (SiteNotFoundException | InvalidRouteArgumentsException $e) {
            throw new UnableToLinkToPageException(sprintf('The link to the page with ID "%d" could not be generated: %s', $pageUid, $e->getMessage()), 1559794913, $e);
        }

        $view->assignMultiple([
            'logoLink' => Typo3Information::URL_COMMUNITY,
            'liveUrl' => $liveUrl,
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
        return $view->renderResponse('Preview/Index');
    }

    /**
     * Evaluate the active state.
     */
    protected function isValidStage($stageArray): bool
    {
        return is_array($stageArray) && !empty($stageArray);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function generateUrl(Site $site, int $pageUid, array $parameters): string
    {
        return (string)$site->getRouter()->generateUri($pageUid, $parameters);
    }
}
