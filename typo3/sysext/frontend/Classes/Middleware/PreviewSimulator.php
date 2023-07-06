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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Context\VisibilityAspect;
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
    public function __construct(protected readonly Context $context)
    {
    }

    /**
     * Evaluates preview settings if a backend user is logged in
     *
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false)) {
            $pageArguments = $request->getAttribute('routing', null);
            if (!$pageArguments instanceof PageArguments) {
                return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                    $request,
                    'Page Arguments could not be resolved',
                    ['code' => PageAccessFailureReasons::INVALID_PAGE_ARGUMENTS]
                );
            }
            if ($this->context->hasAspect('visibility')) {
                $visibilityAspect = $this->context->getAspect('visibility');
            } else {
                $visibilityAspect = GeneralUtility::makeInstance(VisibilityAspect::class);
            }
            // The preview flag is set if the current page turns out to be hidden
            $showHiddenPages = $this->checkIfPageIsHidden($pageArguments->getPageId(), $request);
            $rootlineRequiresPreviewFlag = $this->checkIfRootlineRequiresPreview($pageArguments->getPageId());
            $simulatingDate = $this->simulateDate($request);
            $simulatingGroup = $this->simulateUserGroup($request);
            $showHiddenRecords = $visibilityAspect->includeHidden();
            $isOfflineWorkspace = $this->context->getPropertyFromAspect('workspace', 'id', 0) > 0;
            $isPreview = $simulatingDate || $simulatingGroup || $showHiddenRecords || $showHiddenPages || $isOfflineWorkspace || $rootlineRequiresPreviewFlag;
            if ($this->context->hasAspect('frontend.preview')) {
                $previewAspect = $this->context->getAspect('frontend.preview');
                $isPreview = $previewAspect->isPreview() || $isPreview;
            }
            $previewAspect = GeneralUtility::makeInstance(PreviewAspect::class, $isPreview);
            $this->context->setAspect('frontend.preview', $previewAspect);

            if ($showHiddenPages || $rootlineRequiresPreviewFlag) {
                $newAspect = GeneralUtility::makeInstance(VisibilityAspect::class, true, $visibilityAspect->includeHiddenContent(), $visibilityAspect->includeDeletedRecords());
                $this->context->setAspect('visibility', $newAspect);
            }
        }

        return $handler->handle($request);
    }

    protected function checkIfRootlineRequiresPreview(int $pageId): bool
    {
        $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageId, '', $this->context);
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $this->context);
        $groupRestricted = false;
        $timeRestricted = false;
        $hidden = false;
        try {
            $rootLine = $rootlineUtility->get();
            $pageInfo = $pageRepository->getPage_noCheck($pageId);
            // Only check rootline if the current page has not set extendToSubpages itself
            // @see \TYPO3\CMS\Backend\Routing\PreviewUriBuilder::class
            if (!(bool)($pageInfo['extendToSubpages'] ?? false)) {
                // remove the current page from the rootline
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
            }

        } catch (\Exception) {
            // if the rootline cannot be resolved (404 because of delete placeholder in workspaces for example)
            // we do not want to fail here but rather continue handling the request to trigger the TSFE 404 handling
        } finally {
            // clear the rootline cache to ensure it's cleanly built with the full context later on.
            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
            $cacheManager->getCache('runtime')->flushByTag(RootlineUtility::RUNTIME_CACHE_TAG);
            $cacheManager->getCache('rootline')->flush();
        }
        return $groupRestricted || $timeRestricted || $hidden;
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
        $queryTime = $request->getQueryParams()['ADMCMD_simTime'] ?? false;
        if (!$queryTime) {
            return false;
        }

        $simulatedDate = new \DateTimeImmutable('@' . $queryTime);
        if (!$simulatedDate) {
            return false;
        }

        $GLOBALS['SIM_EXEC_TIME'] = $queryTime;
        $GLOBALS['SIM_ACCESS_TIME'] = $queryTime - $queryTime % 60;
        $this->context->setAspect(
            'date',
            GeneralUtility::makeInstance(
                DateTimeAspect::class,
                $simulatedDate
            )
        );
        return true;
    }

    /**
     * Simulate user group for preview functionality
     * When previewing a page with a usergroup restriction, the parameter ADMCMD_simUser = <groupId> will be added
     * to the preview url. Simulation happens.
     * legacy: via TSFE member variables (->fe_user->user[<groupColumn>])
     * new: via Context::UserAspect
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
        $frontendUser->user[$frontendUser->usergroup_column] = $simulateUserGroup;
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
