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

namespace TYPO3\CMS\Frontend\Page;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Domain\Access\RecordAccessVoter;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Error\Http\ShortcutTargetPageNotFoundException;
use TYPO3\CMS\Core\Error\Http\StatusException;
use TYPO3\CMS\Core\Exception\Page\RootLineException;
use TYPO3\CMS\Core\Page\PageLayoutResolver;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Type\Bitmask\PageTranslationVisibility;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendBackendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Event\AfterPageAndLanguageIsResolvedEvent;
use TYPO3\CMS\Frontend\Event\AfterPageWithRootLineIsResolvedEvent;
use TYPO3\CMS\Frontend\Event\BeforePageIsResolvedEvent;

/**
 * Create the PageInformation object. This is typically fired by a
 * middleware. It does all the heavy lifting, page access checks,
 * resolves shortcuts, workspaces, languages and similar.
 *
 * Possible results:
 * - The fully set up PageInformation object is returned.
 * - A PageInformationCreationFailedException is thrown that contains
 *   an early Response from the ErrorController
 * - A StatusException is thrown when ErrorController itself failed
 *
 * @todo: This class also sets / resets the Context language aspect
 *        as not directly obvious side effect. This can't be refactored
 *        currently due to the dependency of stateful PageRepository to
 *        the stateful singleton Context.
 *
 * @internal
 */
final readonly class PageInformationFactory
{
    public function __construct(
        private Context $context,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
        private RecordAccessVoter $accessVoter,
        private ErrorController $errorController,
        private SysTemplateRepository $sysTemplateRepository,
        private PageLayoutResolver $pageLayoutResolver,
    ) {}

    /**
     * Set up proper PageInformation object later available as
     * 'frontend.page.information' Request attribute.
     *
     * At this point, the Context object already contains relevant preview
     * settings, for instance if a backend user is logged in.
     *
     * As a not obvious side effect, this class also *sets* the
     *
     * @internal Extensions should not call themselves, use events.
     * @throws PageInformationCreationFailedException
     * @throws StatusException
     */
    public function create(ServerRequestInterface $request): PageInformation
    {
        // Set Initial, not yet validated values from routing.
        $pageInformation = new PageInformation();
        $pageInformation->setId($request->getAttribute('routing')->getPageId());
        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids'] ?? false) {
            $mountPoint = (string)($request->getAttribute('routing')->getArguments()['MP'] ?? '');
            // Ensure no additional arguments are given via the &MP=123-345,908-172 (e.g. "/")
            $pageInformation->setMountPoint(preg_replace('/[^0-9,-]/', '', $mountPoint));
        }

        $event = $this->eventDispatcher->dispatch(new BeforePageIsResolvedEvent($request, $pageInformation));
        $pageInformation = $event->getPageInformation();

        $pageInformation = $this->setPageAndRootline($request, $pageInformation);
        $this->checkCrossDomainWithDirectId($request, $pageInformation);

        $event = $this->eventDispatcher->dispatch(new AfterPageWithRootLineIsResolvedEvent($request, $pageInformation));
        if ($event->getResponse()) {
            throw new PageInformationCreationFailedException($event->getResponse(), 1705419743);
        }
        $pageInformation = $event->getPageInformation();

        $pageInformation = $this->settingLanguage($request, $pageInformation);
        $pageInformation = $this->setContentFromPid($request, $pageInformation);
        $pageInformation = $this->setPageLayout($pageInformation);
        $this->checkBackendUserAccess($request, $pageInformation);

        $event = $this->eventDispatcher->dispatch(new AfterPageAndLanguageIsResolvedEvent($request, $pageInformation));
        if ($event->getResponse()) {
            throw new PageInformationCreationFailedException($event->getResponse(), 1705420010);
        }
        $pageInformation = $event->getPageInformation();

        $pageInformation = $this->setSysTemplateRows($request, $pageInformation);
        return $this->setLocalRootLine($request, $pageInformation);
    }

    /**
     * Main lifting. Final page and the matching root line are determined and loaded.
     *
     * Note this methods may be called a second time in case of 'content_from_pid'.
     *
     * @throws PageInformationCreationFailedException
     * @throws StatusException
     */
    protected function setPageAndRootline(ServerRequestInterface $request, PageInformation $pageInformation): PageInformation
    {
        $id = $pageInformation->getId();
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $mountPoint = $pageInformation->getMountPoint();
        $pageRecord = $pageRepository->getPage($id);

        if (empty($pageRecord)) {
            // @todo: This logic could be streamlined is general. The idea of PageRepository->getPage() is
            //        to have *one* query that does all the access checks already, to save queries. Only
            //        if this goes wrong more queries are fired to find out details.
            //        This is ugly. It would be better to query the page without checks and return it, probably
            //        only doing the deleted=1 check. If that does not resolve: 404. Then apply the hidden
            //        and groups checks in PHP on the page record without further queries, to error out
            //        whenever a single step goes wrong. Or have a single method that follows this strategy
            //        and throws dedicated exceptions like a PageHiddenException or similar to catch it here
            //        and act accordingly.
            //        This strategy would turn the guesswork logic below around and would be easier to
            //        maintain and follow in general. It would also clean up the various methods with their
            //        subtle differences: getPage($id), getPage($id, true) and getPage_noCheck($id).
            // PageRepository->getPage() did not return a page. This can have
            // different reasons. We want to error out with different status codes.
            $hiddenField = $GLOBALS['TCA']['pages']['ctrl']['enablecolumns']['disabled'] ?? '';
            $includeHiddenPages = $this->context->getPropertyFromAspect('visibility', 'includeHiddenPages') || $this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false);
            if (!empty($hiddenField) && !$includeHiddenPages) {
                // Page is hidden, user has no access. 404. This is deliberately done in default language
                // since language overlays should not be rendered when default language is hidden.
                $rawPageRecord = $pageRepository->getPage_noCheck($id);
                if ($rawPageRecord === [] || $rawPageRecord[$hiddenField]) {
                    $response = $this->errorController->pageNotFoundAction(
                        $request,
                        'The requested page does not exist!',
                        ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
                    );
                    throw new PageInformationCreationFailedException($response, 1674144383);
                }
            }
            $requestedPageRowWithoutGroupCheck = $pageRepository->getPage($id, true);
            if (!empty($requestedPageRowWithoutGroupCheck)) {
                // We know now the page could not be received, but the reason is *not* that the
                // page is hidden and the user has no hidden access. So group access failed? 403.
                $response = $this->errorController->accessDeniedAction(
                    $request,
                    'ID was not an accessible page',
                    [
                        'code' => PageAccessFailureReasons::ACCESS_DENIED_PAGE_NOT_RESOLVED,
                        'direct_access' => [
                            0 => $requestedPageRowWithoutGroupCheck,
                        ],
                    ],
                );
                throw new PageInformationCreationFailedException($response, 1705325336);
            }
            // Else 404 for 'record not exists' or similar.
            $response = $this->errorController->pageNotFoundAction(
                $request,
                'The requested page does not exist!',
                ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
            );
            throw new PageInformationCreationFailedException($response, 1533931330);
        }

        $pageInformation->setPageRecord($pageRecord);
        $pageDoktype = (int)($pageRecord['doktype']);

        if ($pageDoktype === PageRepository::DOKTYPE_SPACER || $pageDoktype === PageRepository::DOKTYPE_SYSFOLDER) {
            // Spacer and sysfolders are not accessible in frontend
            $response = $this->errorController->pageNotFoundAction(
                $request,
                'The requested page does not exist!',
                ['code' => PageAccessFailureReasons::ACCESS_DENIED_INVALID_PAGETYPE]
            );
            throw new PageInformationCreationFailedException($response, 1533931343);
        }

        if ($pageDoktype === PageRepository::DOKTYPE_SHORTCUT) {
            // Resolve shortcut page target.
            // Clear mount point if page is a shortcut: If the shortcut goes to
            // another page, we leave the rootline which the MP expects.
            $mountPoint = '';
            $pageInformation->setMountPoint($mountPoint);
            // Saving the page so that we can check later - when we know about languages - whether we took the correct shortcut
            // or if a translation of the page overwrites the shortcut target, and we need to follow the new target.
            $pageInformation = $this->settingLanguage($request, $pageInformation);
            // Reset vars to new state that may have been created by settingLanguage()
            $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
            $pageRecord = $pageInformation->getPageRecord();
            $pageInformation->setOriginalShortcutPageRecord($pageRecord);
            try {
                $pageRecord = $pageRepository->resolveShortcutPage($pageRecord, true);
            } catch (ShortcutTargetPageNotFoundException) {
                $response = $this->errorController->pageNotFoundAction(
                    $request,
                    'ID was not an accessible page',
                    ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
                );
                throw new PageInformationCreationFailedException($response, 1705335065);
            }
            $pageInformation->setPageRecord($pageRecord);
            $id = (int)$pageRecord['uid'];
            $pageInformation->setId($id);
            $pageDoktype = (int)($pageRecord['doktype'] ?? 0);
        }

        if ($pageDoktype === PageRepository::DOKTYPE_MOUNTPOINT && $pageRecord['mount_pid_ol']) {
            // If the page is a mount point which should be overlaid with the contents of the mounted page,
            // it must never be accessible directly, but only in the mount point context.
            // We thus change the current page id.
            $originalMountPointPageRecord = $pageRecord;
            $pageInformation->setOriginalMountPointPageRecord($pageRecord);
            $pageRecord = $pageRepository->getPage((int)$originalMountPointPageRecord['mount_pid']);
            if (empty($pageRecord)) {
                // Target mount point page not accessible for some reason.
                $response = $this->errorController->pageNotFoundAction(
                    $request,
                    'The requested page does not exist!',
                    ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
                );
                throw new PageInformationCreationFailedException($response, 1705425523);
            }
            $pageInformation->setPageRecord($pageRecord);
            if ($mountPoint === '' || !empty($pageInformation->getOriginalShortcutPageRecord())) {
                // If the current page is a shortcut, the MP parameter will be replaced
                $mountPoint = $pageRecord['uid'] . '-' . $originalMountPointPageRecord['uid'];
            } else {
                $mountPoint = $mountPoint . ',' . $pageRecord['uid'] . '-' . $originalMountPointPageRecord['uid'];
            }
            $pageInformation->setMountPoint($mountPoint);
            $id = (int)$pageRecord['uid'];
            $pageInformation->setId($id);
        }

        // Get rootLine and error out if it can not be retrieved.
        $pageInformation->setRootLine($this->getRootlineOrThrow($request, $id, $mountPoint));

        // Check 'extendToSubpages' in rootLine and backend user section.
        $this->checkRootlineForIncludeSection($request, $pageInformation);
        return $pageInformation;
    }

    /**
     * Determine the final Context language aspect, page record based on
     * language settings, existing page overlay and its rootLine.
     *
     * May reset:
     * $pageInformation->pageRecord
     * $pageInformation->pageRepository
     * $pageInformation->rootLine
     *
     * @throws PageInformationCreationFailedException
     * @throws StatusException
     */
    protected function settingLanguage(ServerRequestInterface $request, PageInformation $pageInformation): PageInformation
    {
        $site = $request->getAttribute('site');
        $language = $request->getAttribute('language', $site->getDefaultLanguage());
        $languageAspect = LanguageAspectFactory::createFromSiteLanguage($language);
        $languageId = $languageAspect->getId();
        $languageContentId = $languageAspect->getContentId();

        $pageRecord = $pageInformation->getPageRecord();

        $pageTranslationVisibility = new PageTranslationVisibility((int)($pageRecord['l18n_cfg'] ?? 0));
        if ($languageAspect->getId() > 0) {
            // If the incoming language is set to another language than default
            $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
            $olRec = $pageRepository->getPageOverlay($pageRecord, $languageAspect);
            $overlaidLanguageId = (int)($olRec['sys_language_uid'] ?? 0);
            if ($overlaidLanguageId !== $languageAspect->getId()) {
                // If requested translation is not available
                if ($pageTranslationVisibility->shouldHideTranslationIfNoTranslatedRecordExists()) {
                    $response = $this->errorController->pageNotFoundAction(
                        $request,
                        'Page is not available in the requested language.',
                        ['code' => PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE]
                    );
                    throw new PageInformationCreationFailedException($response, 1533931388);
                }
                switch ($languageAspect->getLegacyLanguageMode()) {
                    case 'strict':
                        $response = $this->errorController->pageNotFoundAction(
                            $request,
                            'Page is not available in the requested language (strict).',
                            ['code' => PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE_STRICT_MODE]
                        );
                        throw new PageInformationCreationFailedException($response, 1533931395);
                    case 'content_fallback':
                        // Setting content uid (but leaving the sys_language_uid) when a content_fallback value was found.
                        foreach ($languageAspect->getFallbackChain() as $orderValue) {
                            if ($orderValue === '0' || $orderValue === 0 || $orderValue === '') {
                                $languageContentId = 0;
                                break;
                            }
                            if (MathUtility::canBeInterpretedAsInteger($orderValue) && $overlaidLanguageId === (int)$orderValue) {
                                $languageContentId = (int)$orderValue;
                                break;
                            }
                            if ($orderValue === 'pageNotFound') {
                                // The existing fallbacks have not been found, but instead of continuing page rendering
                                // with default language, a "page not found" message should be shown instead.
                                $response = $this->errorController->pageNotFoundAction(
                                    $request,
                                    'Page is not available in the requested language (fallbacks did not apply).',
                                    ['code' => PageAccessFailureReasons::LANGUAGE_AND_FALLBACKS_NOT_AVAILABLE]
                                );
                                throw new PageInformationCreationFailedException($response, 1533931402);
                            }
                        }
                        break;
                    default:
                        // Default is that everything defaults to the default language.
                        $languageId = ($languageContentId = 0);
                }
            }

            // Define the language aspect again now
            $languageAspect = new LanguageAspect(
                $languageId,
                $languageContentId,
                $languageAspect->getOverlayType(),
                $languageAspect->getFallbackChain()
            );

            // Setting localized page record if an overlay record was found (which it is only if a language is used)
            // Doing this ensures that page properties like the page title are resolved in the correct language.
            $pageInformation->setPageRecord($olRec);
        }

        // Set the final language aspect!
        $this->context->setAspect('language', $languageAspect);

        if ((!$languageAspect->getContentId() || !$languageAspect->getId())
            && $pageTranslationVisibility->shouldBeHiddenInDefaultLanguage()
        ) {
            // If default language is not available
            $response = $this->errorController->pageNotFoundAction(
                $request,
                'Page is not available in default language.',
                ['code' => PageAccessFailureReasons::LANGUAGE_DEFAULT_NOT_AVAILABLE]
            );
            throw new PageInformationCreationFailedException($response, 1533931423);
        }

        if ($languageAspect->getId() > 0) {
            // Updating rootLine with translations if the language key is set.
            $pageInformation->setRootLine(
                $this->getRootlineOrThrow($request, $pageInformation->getId(), $pageInformation->getMountPoint())
            );
        }

        return $pageInformation;
    }

    /**
     * Check the value of 'content_from_pid' of the current page record, to see if the
     * current request should actually show content from another page.
     * If so, PageInformation->getContentFromPid() is set to the page id of the content
     * page, while PageInformation->getId() is kept as the original page id.
     * If there is no 'content_from_pid', PageInformation->getId() and
     * PageInformation->getContentFromPid() end up carrying the same page ids.
     *
     * @throws PageInformationCreationFailedException
     * @throws StatusException
     */
    protected function setContentFromPid(ServerRequestInterface $request, PageInformation $pageInformation): PageInformation
    {
        $contentFromPid = (int)($pageInformation->getPageRecord()['content_from_pid'] ?? 0);
        if ($contentFromPid === 0) {
            // $pageInformation->contentFromPid is always initialized, usually identical with id.
            $pageInformation->setContentFromPid($pageInformation->getId());
            return $pageInformation;
        }
        // Verify content target pid is good and resolves all access restrictions and similar.
        $targetPageInformation = new PageInformation();
        $targetPageInformation->setId($contentFromPid);
        $targetPageInformation = $this->setPageAndRootline($request, $targetPageInformation);
        // Above call did not throw. Set the verified id.
        $pageInformation->setContentFromPid($targetPageInformation->getId());
        return $pageInformation;
    }

    /**
     * Resolve the selected backend layout for the current page and add it to the page information
     */
    protected function setPageLayout(PageInformation $pageInformation): PageInformation
    {
        $pageLayout = $this->pageLayoutResolver->getLayoutForPage(
            $pageInformation->getPageRecord(),
            $pageInformation->getRootLine()
        );
        if ($pageLayout !== null) {
            $pageInformation->setPageLayout($pageLayout);
        }
        return $pageInformation;
    }

    /**
     * Checks if visibility of the page is blocked upwards in the root line.
     *
     * The blocking feature of a page must be turned on by setting the page
     * record field 'extendToSubpages' to 1 for 'hidden', 'starttime', 'endtime'
     * 'fe_group' restrictions to bubble down in rootLine.
     *
     * Additionally, this method checks for backend user sections in root line
     * and if found, evaluates if a backend user is logged in and has access.
     *
     * @throws PageInformationCreationFailedException
     * @throws StatusException
     */
    protected function checkRootlineForIncludeSection(ServerRequestInterface $request, PageInformation $pageInformation): void
    {
        $rootLine = $pageInformation->getRootLine();
        for ($a = 0; $a < count($rootLine); $a++) {
            $rootLineEntry = $rootLine[$a];
            if (!$this->accessVoter->accessGrantedForPageInRootLine($rootLineEntry, $this->context)) {
                // accessGrantedForPageInRootLine() does the main check for 'extendToSubpages'.
                $response = $this->errorController->accessDeniedAction(
                    $request,
                    'Subsection was found and not accessible',
                    [
                        'code' => PageAccessFailureReasons::ACCESS_DENIED_SUBSECTION_NOT_RESOLVED,
                        'sub_section' => [
                            0 => $rootLineEntry,
                        ],
                    ],
                );
                throw new PageInformationCreationFailedException($response, 1705337296);
            }
            if ((int)$rootLineEntry['doktype'] === PageRepository::DOKTYPE_BE_USER_SECTION) {
                // Only logged in backend users with 'PAGE_SHOW' permissions are allowed to FE render backend user sections.
                $isBackendUserLoggedIn = $this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false);
                if (!$isBackendUserLoggedIn) {
                    $response = $this->errorController->accessDeniedAction(
                        $request,
                        'Subsection was found and not accessible',
                        ['code' => PageAccessFailureReasons::ACCESS_DENIED_SUBSECTION_NOT_RESOLVED]
                    );
                    throw new PageInformationCreationFailedException($response, 1705416744);
                }
                if (!$this->getBackendUser()->doesUserHaveAccess($pageInformation->getPageRecord(), Permission::PAGE_SHOW)) {
                    $response = $this->errorController->accessDeniedAction(
                        $request,
                        'Subsection was found and not accessible',
                        ['code' => PageAccessFailureReasons::ACCESS_DENIED_SUBSECTION_NOT_RESOLVED]
                    );
                    throw new PageInformationCreationFailedException($response, 1705337701);
                }
            }
        }
    }

    /**
     * When calling a page with a direct id 'https://my.domain/?id=123',
     * this the site object of 'my.domain' is determined from an earlier middleware.
     * If now '123' is *not* a (sub) page of the 'my.domain' site, we error out since
     * we don't want to directly render content of a different site page within "our" site.
     * Except if '123' is a shortcut, we still allow it, since that will trigger a
     * redirect to the url with the shortcut target domain later.
     *
     * @throws PageInformationCreationFailedException
     * @throws StatusException
     */
    protected function checkCrossDomainWithDirectId(ServerRequestInterface $request, PageInformation $pageInformation): void
    {
        $directlyRequestedId = (int)($request->getQueryParams()['id'] ?? 0);
        $shortcutId = (int)($pageInformation->getOriginalShortcutPageRecord()['uid'] ?? 0);
        if ($directlyRequestedId && $shortcutId !== $directlyRequestedId) {
            $rootLine = $pageInformation->getRootLine();
            $siteRootPageId = $request->getAttribute('site')->getRootPageId();
            $siteRootWithinRootlineFound = false;
            foreach ($rootLine as $pageInRootLine) {
                if ((int)$pageInRootLine['uid'] === $siteRootPageId) {
                    $siteRootWithinRootlineFound = true;
                    break;
                }
            }
            if (!$siteRootWithinRootlineFound) {
                $response = $this->errorController->pageNotFoundAction(
                    $request,
                    'ID was outside the domain',
                    ['code' => PageAccessFailureReasons::ACCESS_DENIED_HOST_PAGE_MISMATCH]
                );
                throw new PageInformationCreationFailedException($response, 1705397417);
            }
        }
    }

    /**
     * When a backend user is logged in, it needs at least 'show' permissions.
     *
     * @throws PageInformationCreationFailedException
     * @throws StatusException
     */
    protected function checkBackendUserAccess(ServerRequestInterface $request, PageInformation $pageInformation): void
    {
        // No backend user was logged in, nothing to check
        if (!$this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false)) {
            return;
        }
        // PreviewSimulator did not detect anything
        if (!$this->context->getPropertyFromAspect('frontend.preview', 'isPreview', false)) {
            return;
        }
        // Editor has no show permission for this page PLUS regular user is not allowed to see the page? 403.
        if (!$GLOBALS['BE_USER']->doesUserHaveAccess($pageInformation->getPageRecord(), Permission::PAGE_SHOW)
            && !$this->accessVoter->accessGranted('pages', $pageInformation->getPageRecord(), $this->context)
        ) {
            $response = $this->errorController->accessDeniedAction(
                $request,
                'ID was not an accessible page',
                ['code' => PageAccessFailureReasons::ACCESS_DENIED_PAGE_NOT_RESOLVED]
            );
            throw new PageInformationCreationFailedException($response, 1705422293);
        }
    }

    /**
     * Determine relevant sys_template rows and set to PageInformation object.
     *
     * @todo: Even though all rootline sys_template records are fetched with only one query
     *        in below implementation, we could potentially join or sub select sys_template
     *        records already when pages rootline is queried. This will save one query.
     *        This could be done when we manage to switch PageRepository / RootlineUtility to a CTE.
     * @throws PageInformationCreationFailedException
     * @throws StatusException
     */
    protected function setSysTemplateRows(ServerRequestInterface $request, PageInformation $pageInformation): PageInformation
    {
        $site = $request->getAttribute('site');
        $rootLine = $pageInformation->getRootLine();
        if ($site instanceof Site && $site->isTypoScriptRoot()) {
            $rootLineUntilSite = [];
            foreach ($rootLine as $index => $rootlinePage) {
                $rootLineUntilSite[$index] = $rootlinePage;
                $pageId = (int)($rootlinePage['uid'] ?? 0);
                if ($pageId === $site->getRootPageId()) {
                    break;
                }
            }
            $rootLine = $rootLineUntilSite;
        }
        $sysTemplateRows = $this->sysTemplateRepository->getSysTemplateRowsByRootline($rootLine, $request);
        $pageInformation->setSysTemplateRows($sysTemplateRows);
        return $pageInformation;
    }

    /**
     * Calculate "local" rootLine that stops at first root=1 template.
     */
    protected function setLocalRootLine(ServerRequestInterface $request, PageInformation $pageInformation): PageInformation
    {
        $site = $request->getAttribute('site');
        $sysTemplateRows = $pageInformation->getSysTemplateRows();
        $rootLine = $pageInformation->getRootLine();
        $sysTemplateRowsIndexedByPid = array_combine(array_column($sysTemplateRows, 'pid'), $sysTemplateRows);
        $localRootline = [];
        foreach ($rootLine as $rootlinePage) {
            array_unshift($localRootline, $rootlinePage);
            $pageId = (int)($rootlinePage['uid'] ?? 0);
            if ($pageId === $site->getRootPageId() && $site->isTypoScriptRoot()) {
                break;
            }
            if ($pageId > 0 && (int)($sysTemplateRowsIndexedByPid[$pageId]['root'] ?? 0) === 1) {
                break;
            }
        }
        $pageInformation->setLocalRootLine($localRootline);
        return $pageInformation;
    }

    /**
     * @return non-empty-array
     * @throws PageInformationCreationFailedException
     * @throws StatusException
     */
    protected function getRootlineOrThrow(ServerRequestInterface $request, int $pageId, string $mountPoint): array
    {
        $rootLine = [];
        try {
            $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageId, $mountPoint)->get();
        } catch (RootLineException) {
            // Empty / broken rootline handled below.
        }
        if (!empty($rootLine)) {
            // All good.
            return $rootLine;
        }
        // Error case: Log and render error.
        $message = 'The requested page did not have a proper connection to the tree root!';
        $context = [
            'pageId' => $pageId,
            'requestUrl' => $request->getAttribute('normalizedParams')->getRequestUrl(),
        ];
        $this->logger->error($message, $context);
        try {
            $response = $this->errorController->internalErrorAction(
                $request,
                $message,
                ['code' => PageAccessFailureReasons::ROOTLINE_BROKEN]
            );
            throw new PageInformationCreationFailedException($response, 1533931350);
        } catch (StatusException $up) {
            $this->logger->error($message, ['exception' => $up]);
            throw $up;
        }
    }

    protected function getBackendUser(): ?FrontendBackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
