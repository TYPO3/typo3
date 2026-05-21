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
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\PageRendererBackendSetupTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Preview\PreviewSimulatorCalculator;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Implements the preview controller of the workspace module.
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
final readonly class PreviewController
{
    use PageRendererBackendSetupTrait;

    public function __construct(
        private WorkspaceService $workspaceService,
        private PageRenderer $pageRenderer,
        private UriBuilder $uriBuilder,
        private SiteFinder $siteFinder,
        private Context $context,
        private BackendViewFactory $backendViewFactory,
        private ExtensionConfiguration $extensionConfiguration,
        private LanguageServiceFactory $languageServiceFactory,
        private PreviewSimulatorCalculator $previewSimulatorCalculator,
    ) {}

    /**
     * Basically makes sure that the workspace preview is rendered.
     * The preview itself consists of three frames, so there are
     * only the frames-urls we have to generate here.
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $queryParameters = $request->getQueryParams();
        $pageUid = (int)$queryParameters['id'];
        $backendUser = $this->getBackendUser();
        $activeWorkspace = $backendUser->workspace;

        $this->setUpBasicPageRendererForBackend(
            $this->pageRenderer,
            $this->extensionConfiguration,
            $request,
            $this->languageServiceFactory->createFromUserPreferences($this->getBackendUser())
        );
        $view = $this->backendViewFactory->create($request, ['typo3/cms-workspaces']);

        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/dropdown.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/workspaces/preview.js');
        $this->pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_edit'));
        $this->pageRenderer->addInlineSetting('RecordHistory', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_history'));
        $this->pageRenderer->addCssFile('EXT:workspaces/Resources/Public/Css/preview.css');
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
        $this->pageRenderer->addInlineSetting('Workspaces', 'id', $pageUid);

        $availableWorkspaces = $this->workspaceService->getAvailableWorkspaces();
        try {
            $liveUrl = false;
            $site = $this->siteFinder->getSiteByPageId($pageUid);
            // Remove GET parameters related to the workspaces module
            unset($queryParameters['route'], $queryParameters['token'], $queryParameters['previewWS']);
            if (isset($queryParameters['L'])) {
                $queryParameters['_language'] = $site->getLanguageById((int)$queryParameters['L']);
                unset($queryParameters['L']);
            }

            // Build URL for live version of page
            $page = BackendUtility::getRecord('pages', $pageUid);
            $rootline = BackendUtility::BEgetRootLine($pageUid);
            $queryParametersLive = PreviewUriBuilder::getAdditionalQueryParametersForAccessRestrictedPages(
                $page,
                clone $this->context,
                $rootline
            );
            if (!$this->workspaceService->isNewPage($pageUid)) {
                $parameters = array_merge(
                    $queryParameters,
                    $queryParametersLive,
                    ['ADMCMD_prev' => 'LIVE']
                );
                // temporarily set workspace aspect to live (0) to generate the live URL
                $liveWorkspaceContext = clone $this->context;
                $liveWorkspaceContext->setAspect('workspace', new WorkspaceAspect(0));
                $liveUrl = $this->generateUrl($site, $pageUid, $parameters, $liveWorkspaceContext);
            }

            // Build URL for draft version of page
            $page = BackendUtility::getRecordWSOL('pages', $pageUid);
            $rootline = BackendUtility::BEgetRootLine($pageUid, '', true);
            $queryParametersDraft = PreviewUriBuilder::getAdditionalQueryParametersForAccessRestrictedPages(
                $page,
                clone $this->context,
                $rootline
            );
            // The frontend checks access on the live version and on the draft version overlaying it,
            // so the simulated user has to pass the restrictions of both.
            $simulatedUserGroups = $this->previewSimulatorCalculator->mergeUserGroups(
                (string)($queryParametersLive['ADMCMD_simUser'] ?? ''),
                (string)($queryParametersDraft['ADMCMD_simUser'] ?? '')
            );
            unset($queryParametersDraft['ADMCMD_simUser']);
            if ($simulatedUserGroups !== '') {
                $queryParametersDraft['ADMCMD_simUser'] = $simulatedUserGroups;
            }
            $parameters = array_merge(
                $queryParameters,
                $queryParametersDraft,
                ['ADMCMD_prev' => 'IGNORE']
            );
            // Without a frontend user, the draft version of a restricted page is not found when
            // generating the URL, so the simulated groups are applied to the context as well,
            // plus -2 ("show at any login"), which every logged-in frontend user has.
            $draftContext = clone $this->context;
            if ($simulatedUserGroups !== '') {
                $draftContext->setAspect('frontend.user', new UserAspect(null, [-2, ...$this->previewSimulatorCalculator->getUserGroupIds($simulatedUserGroups)]));
            }
            $wsUrl = $this->generateUrl($site, $pageUid, $parameters, $draftContext);
        } catch (SiteNotFoundException|InvalidRouteArgumentsException $e) {
            throw new UnableToLinkToPageException(sprintf('The link to the page with ID "%d" could not be generated: %s', $pageUid, $e->getMessage()), 1559794913, $e);
        }

        $view->assignMultiple([
            'logoLink' => Typo3Information::URL_COMMUNITY,
            'liveUrl' => $liveUrl,
            'wsUrl' => $wsUrl,
            'activeWorkspace' => $availableWorkspaces[$activeWorkspace],
            'splitPreviewModes' => $splitPreviewModes,
            'firstPreviewMode' => current($splitPreviewModes),
        ]);
        $this->pageRenderer->setBodyContent('<body>' . $view->render('Preview/Index'));
        return new HtmlResponse($this->pageRenderer->render($request));
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    private function generateUrl(Site $site, int $pageUid, array $parameters, ?Context $context = null): string
    {
        return (string)$site->getRouter($context ?? $this->context)->generateUri($pageUid, $parameters);
    }
}
