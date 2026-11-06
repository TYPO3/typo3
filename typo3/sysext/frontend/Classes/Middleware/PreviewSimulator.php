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

namespace TYPO3\CMS\Frontend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Domain\Access\RecordAccessVoter;
use TYPO3\CMS\Core\Domain\DateTimeFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Schema\LanguageMarker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Aspect\PreviewAspect;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;
use TYPO3\CMS\Frontend\Preview\PreviewSimulatorCalculator;

/**
 * Middleware for handling preview settings
 * used when simulating / previewing pages or content through query params when
 * previewing access or time restricted content via for example backend preview links
 */
readonly class PreviewSimulator implements MiddlewareInterface
{
    public function __construct(
        protected Context $context,
        protected RecordAccessVoter $accessVoter,
        protected PreviewSimulatorCalculator $previewSimulatorCalculator = new PreviewSimulatorCalculator(),
    ) {}

    /**
     * Evaluates preview settings if a backend user is logged in
     *
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isLoggedIn = $this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false);
        $isOfflineWorkspace = $this->context->getPropertyFromAspect('workspace', 'isOffline', false);
        // When previewing a workspace with the preview link, the PreviewUserAuthentication is NOT marked as
        // "isLoggedIn" as it does not have a valid user ID. For this reason, we also check if the Workspace is offline. See WorkspacePreview middleware
        if ($isLoggedIn || $isOfflineWorkspace) {
            $pageArguments = $request->getAttribute('routing', null);
            if (!$pageArguments instanceof PageArguments) {
                return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                    $request,
                    'Page Arguments could not be resolved',
                    ['code' => PageAccessFailureReasons::INVALID_PAGE_ARGUMENTS]
                );
            }
            $visibilityAspect = $this->context->getAspect('visibility');
            // Simulate date and user group first, so the rootline check below
            // evaluates against the simulated context.
            $simulatingDate = $this->simulateDate($request);
            $simulatingGroup = $this->simulateUserGroup($request);
            // The preview flag is set if the current page turns out to be hidden
            $showHiddenPages = $this->checkIfPageIsHidden($pageArguments->getPageId(), $request);
            $rootlineRequiresPreviewFlag = $this->checkIfRootlineRequiresPreview($pageArguments->getPageId());
            $showHiddenRecords = $visibilityAspect->includeHidden();
            $isPreview = $simulatingDate || $simulatingGroup || $showHiddenRecords || $showHiddenPages || $isOfflineWorkspace || $rootlineRequiresPreviewFlag;
            if ($this->context->hasAspect('frontend.preview')) {
                /** @var PreviewAspect $previewAspect */
                $previewAspect = $this->context->getAspect('frontend.preview');
                $isPreview = $previewAspect->isPreview() || $isPreview;
            }
            $this->context->setAspect('frontend.preview', new PreviewAspect($isPreview));

            if ($showHiddenPages || $rootlineRequiresPreviewFlag) {
                $this->context->setAspect('visibility', $visibilityAspect->withIncludeHiddenPages(true));
            }
        }

        return $handler->handle($request);
    }

    /**
     * Evaluate if any ancestor page, which sets "extendToSubpages", restricts access
     * for the current request. The current page itself is not checked.
     */
    protected function checkIfRootlineRequiresPreview(int $pageId): bool
    {
        $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageId, '', $this->context);
        try {
            $rootLine = $rootlineUtility->get();
            // Remove the current page from the rootline
            array_shift($rootLine);
            foreach ($rootLine as $page) {
                if (!$this->accessVoter->accessGrantedForPageInRootLine($page, $this->context)) {
                    return true;
                }
            }
        } catch (\Exception) {
            // if the rootline cannot be resolved (404 because of delete placeholder in workspaces for example)
            // we do not want to fail here but rather continue handling the request to trigger the middleware 404 handling
        }
        return false;
    }

    /**
     * Checks if the page is hidden in the active workspace + language setup.
     */
    protected function checkIfPageIsHidden(int $pageId, ServerRequestInterface $request): bool
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $this->context);
        $site = $request->getAttribute('site', null);
        // always check both the page in the requested language and the page in the default language, as due to the
        // overlay handling, a hidden default page will require setting the preview flag to allow previewing of the
        // translation
        $languageAspectFromRequest = LanguageAspectFactory::createFromSiteLanguage($request->getAttribute('language', $site->getDefaultLanguage()));
        $pageIsHidden = $pageRepository->checkIfPageIsHidden($pageId, $languageAspectFromRequest);

        if ($languageAspectFromRequest->getId() > LanguageMarker::DEFAULT_LANGUAGE) {
            $pageIsHidden = $pageIsHidden || $pageRepository->checkIfPageIsHidden(
                $pageId,
                LanguageAspectFactory::createFromSiteLanguage($site->getDefaultLanguage())
            );
        }
        return $pageIsHidden;
    }

    /**
     * Simulate dates for preview functionality
     * When previewing a time restricted page from the backend, the parameter ADMCMD_simTime it added containing
     * a timestamp with the time to preview. The globals 'SIM_EXEC_TIME' and 'SIM_ACCESS_TIME' and the 'DateTimeAspect'
     * are used to simulate rendering at that point in time.
     * Ideally the global access is removed in future versions.
     * This functionality needs to be loaded after BackendAuthenticator as it is only relevant for
     * logged in backend users and needs to be done before any page resolving starts.
     */
    protected function simulateDate(ServerRequestInterface $request): bool
    {
        $queryTime = (int)($request->getQueryParams()['ADMCMD_simTime'] ?? 0);
        if ($queryTime === 0) {
            return false;
        }

        $GLOBALS['SIM_EXEC_TIME'] = $queryTime;
        $GLOBALS['SIM_ACCESS_TIME'] = $queryTime - $queryTime % 60;
        $this->context->setAspect('date', new DateTimeAspect(DateTimeFactory::createFromTimestamp($queryTime)));
        return true;
    }

    /**
     * Simulate user group for preview functionality. When previewing a page with a user group restriction,
     * the parameter ADMCMD_simUser = <groupId(s)> will be added to the preview url. Simulation happens.
     * Multiple group IDs can be provided as a comma-separated list (e.g. ADMCMD_simUser=1,2) to simulate
     * a user that simultaneously satisfies multiple group restrictions, e.g. when a page's live and workspace
     * versions are protected by different groups.
     * This functionality needs to be loaded after BackendAuthenticator as it is only relevant for
     * logged in backend users and needs to be done before any page resolving starts.
     * If the current frontend user is already member of all these groups, nothing needs to be simulated,
     * and the real frontend user is kept.
     */
    protected function simulateUserGroup(ServerRequestInterface $request): bool
    {
        $simulatedUserGroups = $request->getQueryParams()['ADMCMD_simUser'] ?? '';
        if (!is_scalar($simulatedUserGroups)) {
            return false;
        }
        $simulateUserGroups = $this->previewSimulatorCalculator->getUserGroupIds((string)$simulatedUserGroups);
        if ($simulateUserGroups === []) {
            return false;
        }
        if (array_diff($simulateUserGroups, $this->context->getPropertyFromAspect('frontend.user', 'groupIds', [])) === []) {
            return false;
        }
        $frontendUser = $request->getAttribute('frontend.user');
        $frontendUser->user[$frontendUser->usergroup_column] = implode(',', $simulateUserGroups);
        foreach ($simulateUserGroups as $simulateUserGroup) {
            $frontendUser->userGroups[$simulateUserGroup] = [
                'uid' => $simulateUserGroup,
                'title' => '_PREVIEW_',
            ];
        }
        // let's fake having a user with that group, too
        $frontendUser->user[$frontendUser->userid_column] = PHP_INT_MAX;
        // Set this option so the is_online timestamp is not updated in updateOnlineTimestamp()
        $frontendUser->user['is_online'] = $this->context->getPropertyFromAspect('date', 'timestamp');
        $this->context->setAspect('frontend.user', $frontendUser->createUserAspect());
        return true;
    }
}
