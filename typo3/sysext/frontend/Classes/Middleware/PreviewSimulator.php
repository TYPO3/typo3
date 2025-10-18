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
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Domain\DateTimeFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Aspect\PreviewAspect;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * Middleware for handling preview settings
 * used when simulating / previewing pages or content through query params when
 * previewing access or time restricted content via for example backend preview links
 */
class PreviewSimulator implements MiddlewareInterface
{
    public function __construct(protected readonly Context $context) {}

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
            // The preview flag is set if the current page turns out to be hidden
            $showHiddenPages = $this->checkIfPageIsHidden($pageArguments->getPageId(), $request);
            $rootlineRequiresPreviewFlag = $this->checkIfRootlineRequiresPreview($pageArguments->getPageId());
            $simulatingDate = $this->simulateDate($request);
            $simulatingGroup = $this->simulateUserGroup($request);
            $showHiddenRecords = $visibilityAspect->includeHidden();
            $isPreview = $simulatingDate || $simulatingGroup || $showHiddenRecords || $showHiddenPages || $isOfflineWorkspace || $rootlineRequiresPreviewFlag;
            if ($this->context->hasAspect('frontend.preview')) {
                /** @var PreviewAspect $previewAspect */
                $previewAspect = $this->context->getAspect('frontend.preview');
                $isPreview = $previewAspect->isPreview() || $isPreview;
            }
            $this->context->setAspect('frontend.preview', new PreviewAspect($isPreview));

            if ($showHiddenPages || $rootlineRequiresPreviewFlag) {
                // @todo: We should implement RecordAccessVoter->isRecordScheduled() once we have the full record (also in workspace)
                // Note: this also renders all hidden and scheduled content on the page. We do not have a different solution to detect this other than
                // "If the page is hidden, we assume the editor wants to see everything on it, including hidden content and scheduled content / pages"
                $newAspect = new VisibilityAspect(true, true, $visibilityAspect->includeDeletedRecords(), true);
                $this->context->setAspect('visibility', $newAspect);
            } elseif ($isOfflineWorkspace) {
                // See WorkspacePreview middleware
                // We currently need to "re-set" this properly, as it is possible that the workspace preview does not load
                // this information properly.
                // @todo: this should be gone completely, as we now fall back to a state that was modified in the WorkspacePreview
                //        middleware. In the mid-termin, we should evaluate the returned values as is right here.
                $newAspect = new VisibilityAspect(false, $visibilityAspect->includeHiddenContent(), $visibilityAspect->includeDeletedRecords(), $simulatingDate);
                $this->context->setAspect('visibility', $newAspect);
            }
        }

        return $handler->handle($request);
    }

    /**
     * Evaluate if the "extendToSubpages" flag was set on any of the previous ancestor pages,
     * but be sure to not check for the current page itself.
     */
    protected function checkIfRootlineRequiresPreview(int $pageId): bool
    {
        $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageId, '', $this->context);
        $groupRestricted = false;
        $timeRestricted = false;
        $hidden = false;
        try {
            $rootLine = $rootlineUtility->get();

            // Remove the current page from the rootline
            array_shift($rootLine);
            foreach ($rootLine as $page) {
                // Skip root node and pages which do not define extendToSubpages
                if ((int)($page['uid'] ?? 0) === 0 || !(bool)($page['extendToSubpages'] ?? false)) {
                    continue;
                }
                $groupRestricted = (bool)(string)($page['fe_group'] ?? '');
                $timeRestricted = (int)($page['starttime'] ?? 0) || (int)($page['endtime'] ?? 0);
                $hidden = (int)($page['hidden'] ?? 0);
                // Stop as soon as a page in the rootline has extendToSubpages set
                break;
            }
        } catch (\Exception) {
            // if the rootline cannot be resolved (404 because of delete placeholder in workspaces for example)
            // we do not want to fail here but rather continue handling the request to trigger the middleware 404 handling
        }
        return $groupRestricted || $timeRestricted || $hidden;
    }

    /**
     * Checks if the page is hidden in the active workspace + language setup.
     *
     * @todo: In the midterm, the pageRepository method should not be evaluated here, but we should rather
     *        work with full records at this point, dealing with values in RecordAccessVoter at this very place
     *        already.
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

        if ($languageAspectFromRequest->getId() > 0) {
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
     * the parameter ADMCMD_simUser = <groupId> will be added to the preview url. Simulation happens.
     * This functionality needs to be loaded after BackendAuthenticator as it is only relevant for
     * logged in backend users and needs to be done before any page resolving starts.
     */
    protected function simulateUserGroup(ServerRequestInterface $request): bool
    {
        $simulateUserGroup = (int)($request->getQueryParams()['ADMCMD_simUser'] ?? 0);
        if (!$simulateUserGroup) {
            return false;
        }
        $frontendUser = $request->getAttribute('frontend.user');
        $frontendUser->user[$frontendUser->usergroup_column] = (string)$simulateUserGroup;
        $frontendUser->userGroups[$simulateUserGroup] = [
            'uid' => $simulateUserGroup,
            'title' => '_PREVIEW_',
        ];
        // let's fake having a user with that group, too
        $frontendUser->user[$frontendUser->userid_column] = PHP_INT_MAX;
        // Set this option so the is_online timestamp is not updated in updateOnlineTimestamp()
        $frontendUser->user['is_online'] = $this->context->getPropertyFromAspect('date', 'timestamp');
        $this->context->setAspect('frontend.user', $frontendUser->createUserAspect());
        return true;
    }
}
