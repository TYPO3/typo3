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

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Domain\Access\RecordAccessVoter;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Error\Http\AbstractServerErrorException;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\Error\Http\ShortcutTargetPageNotFoundException;
use TYPO3\CMS\Core\Exception\Page\RootLineException;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Type\Bitmask\PageTranslationVisibility;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Aspect\PreviewAspect;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Event\AfterPageAndLanguageIsResolvedEvent;
use TYPO3\CMS\Frontend\Event\AfterPageWithRootLineIsResolvedEvent;
use TYPO3\CMS\Frontend\Event\BeforePageIsResolvedEvent;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;
use TYPO3\CMS\Frontend\Page\PageInformation;

/**
 * Creates an instance of TypoScriptFrontendController and makes this globally available
 * via $GLOBALS['TSFE'].
 *
 * In addition, determineId builds up the rootline based on a valid frontend-user authentication and
 * Backend permissions if previewing.
 *
 * @internal this middleware might get removed later.
 */
final class TypoScriptFrontendInitialization implements MiddlewareInterface
{
    use LoggerAwareTrait;

    /**
     * Is set to > 0 if the page could not be resolved. This will then result in early returns when resolving the page.
     *
     * @todo: Property needs to fall and class should no longer be marked "shared: false"
     */
    private int $pageNotFound = 0;

    /**
     * Array containing a history of why a requested page was not accessible.
     *
     * @todo: Property needs to fall and class should no longer be marked "shared: false"
     */
    private array $pageAccessFailureHistory = [];

    public function __construct(
        private readonly Context $context,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TimeTracker $timeTracker,
    ) {}

    /**
     * Creates an instance of TSFE and sets it as a global variable.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // The cache information attribute may be set by previous middlewares already. Make sure we have one from now on.
        $cacheInstruction = $request->getAttribute('frontend.cache.instruction', new CacheInstruction());
        $request = $request->withAttribute('frontend.cache.instruction', $cacheInstruction);

        // Make sure frontend.preview is given from now on.
        if (!$this->context->hasAspect('frontend.preview')) {
            $this->context->setAspect('frontend.preview', new PreviewAspect());
        }
        // If the frontend is showing a preview, caching MUST be disabled.
        if ($this->context->getPropertyFromAspect('frontend.preview', 'isPreview', false)) {
            // @todo: To disentangle this, the preview aspect could be dropped and middlewares that set isPreview true
            //        could directly set $cacheInstruction->disableCache() instead.
            $cacheInstruction->disableCache('EXT:frontend: Disabled cache due to enabled frontend.preview aspect isPreview.');
        }

        $site = $request->getAttribute('site');
        $pageArguments = $request->getAttribute('routing');
        if (!$pageArguments instanceof PageArguments) {
            throw new \RuntimeException(
                'PageArguments request attribute "routing" not found or valid. A previous middleware should have prepared this.',
                1703150865
            );
        }

        // @todo: It would be better to move the initial creation of PageInformation into 'determineId()'
        //        and let the method return that object. This would make code flow more clean and allows
        //        extraction of 'determineId()' to a (stateless) service class.
        $pageInformation = new PageInformation();
        $pageInformation->setId($pageArguments->getPageId());
        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids'] ?? false) {
            $mountPoint = (string)($pageArguments->getArguments()['MP'] ?? '');
            // Ensure no additional arguments are given via the &MP=123-345,908-172 (e.g. "/")
            $mountPoint = preg_replace('/[^0-9,-]/', '', $mountPoint);
            $pageInformation->setMountPoint($mountPoint);
        }

        $directResponse = $this->determineId($request, $pageInformation);
        if ($directResponse) {
            return $directResponse;
        }
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $controller = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            $this->context,
            $site,
            $request->getAttribute('language', $site->getDefaultLanguage()),
            $pageArguments
        );
        // b/w compat layer
        $controller->id = $pageInformation->getId();
        $controller->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $controller->page = $pageInformation->getPageRecord();
        $controller->MP = $pageInformation->getMountPoint();
        $controller->contentPid = $pageInformation->getContentFromPid();
        $controller->rootLine = $pageInformation->getRootLine();

        // Update SYS_LASTCHANGED at the very last, when $this->page might be changed
        // by settingLanguage() and the $this->page was finally resolved.
        // Is also called when a translated page is in use, so the register reflects
        // the state of the translated page, not the page in the default language.
        $pageRecord = $pageInformation->getPageRecord();
        $controller->register['SYS_LASTCHANGED'] = (int)$pageRecord['tstamp'];
        if ($controller->register['SYS_LASTCHANGED'] < (int)$pageRecord['SYS_LASTCHANGED']) {
            $controller->register['SYS_LASTCHANGED'] = (int)$pageRecord['SYS_LASTCHANGED'];
        }

        // Check if backend user has read access to this page.
        if ($this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false)
            && $this->context->getPropertyFromAspect('frontend.preview', 'isPreview', false)
            && !$GLOBALS['BE_USER']->doesUserHaveAccess($controller->page, Permission::PAGE_SHOW)
        ) {
            return GeneralUtility::makeInstance(ErrorController::class)->accessDeniedAction(
                $request,
                'ID was not an accessible page',
                $this->getPageAccessFailureReasons(PageAccessFailureReasons::ACCESS_DENIED_PAGE_NOT_RESOLVED)
            );
        }

        $request = $request->withAttribute('frontend.controller', $controller);
        // Make TSFE globally available
        // @todo deprecate $GLOBALS['TSFE'] once TSFE is retrieved from the
        //       PSR-7 request attribute frontend.controller throughout TYPO3 core
        $GLOBALS['TSFE'] = $controller;
        return $handler->handle($request);
    }

    /**
     * Set up proper PageInformation object later available as
     * 'frontend.page.information' Request attribute.
     *
     * At this point, the Context object already contains relevant preview
     * settings - e.g. if a backend user is logged in.
     *
     * @internal public since it is directly called as hack by RedirectService. Will change.
     */
    public function determineId(ServerRequestInterface $request, PageInformation $pageInformation): ?ResponseInterface
    {
        $event = $this->eventDispatcher->dispatch(new BeforePageIsResolvedEvent($request, $pageInformation));
        $pageInformation = $event->getPageInformation();

        $site = $request->getAttribute('site');

        $this->timeTracker->push('determineId rootLine/');
        try {
            // Sets ->page and ->rootline information based on ->id. ->id may change during this operation.
            // If the found Page ID is not within the site, then pageNotFound is set.
            $this->getPageAndRootline($request, $pageInformation);
            // Checks if the rootPageId of the site is in the resolved rootLine.
            // This is necessary so that references to page-id's via ?id=123 from other sites are not possible.
            $siteRootWithinRootlineFound = false;
            $rootLine = $pageInformation->getRootLine();
            foreach ($rootLine as $pageInRootLine) {
                if ((int)$pageInRootLine['uid'] === $site->getRootPageId()) {
                    $siteRootWithinRootlineFound = true;
                    break;
                }
            }
            // Page is 'not found' in case the id was outside the domain, code 3.
            // This can only happen if there was a shortcut. So $pageInformation->pageRecord is now the shortcut target,
            // but the original page is in $pageInformation->originalShortcutPageRecord. This only happens if people actually
            // call TYPO3 with index.php?id=123 where 123 is in a different page tree, which is not allowed.
            // Render the site root page instead.
            $directlyRequestedId = (int)($request->getQueryParams()['id'] ?? 0);
            if (!$siteRootWithinRootlineFound && $directlyRequestedId && (int)($pageInformation->getOriginalShortcutPageRecord()['uid'] ?? 0) !== $directlyRequestedId) {
                $this->pageNotFound = 3;
                $pageInformation->setId($site->getRootPageId());
                // re-get the page and rootline if the id was not found.
                $this->getPageAndRootline($request, $pageInformation);
            }
        } catch (ShortcutTargetPageNotFoundException) {
            $this->pageNotFound = 1;
        }
        $this->timeTracker->pull();

        $event = $this->eventDispatcher->dispatch(new AfterPageWithRootLineIsResolvedEvent($request, $pageInformation));
        $pageInformation = $event->getPageInformation();
        if ($event->getResponse()) {
            return $event->getResponse();
        }

        $response = null;
        try {
            $this->evaluatePageNotFound($this->pageNotFound, $request);
            // Setting language and fetch translated page
            $pageInformation = $this->settingLanguage($request, $pageInformation);
            // Check the "content_from_pid" field of the resolved page
            $pageInformation->setContentFromPid($this->resolveContentPid($request, $pageInformation));
        } catch (PropagateResponseException $e) {
            $response = $e->getResponse();
        }

        $event = $this->eventDispatcher->dispatch(new AfterPageAndLanguageIsResolvedEvent($request, $pageInformation, $response));
        $pageInformation = $event->getPageInformation();
        // @todo: Change signature of method to always throw 'early' exceptions with response, if one is created.
        //        Catch in calling method. After that, return the 'final' pageInformation object here instead
        //        to follow 'normal' code flow.
        return $event->getResponse();
    }

    /**
     * Loads the page and root line records.
     *
     * A final page and the matching root line are determined and loaded by
     * the algorithm defined by this method.
     *
     * First it loads the initial page from the page repository for given page uid.
     * If that can't be loaded directly, it gets the root line for given page uid.
     * It walks up the root line towards the root page until the page
     * repository can deliver a page record. (The loading restrictions of
     * the root line records are more liberal than that of the page record.)
     *
     * Now the page type is evaluated and handled if necessary. If the page is
     * a shortcut, it is replaced by the target page. If the page is a mount
     * point in overlay mode, the page is replaced by the mounted page.
     *
     * After this potential replacements are done, the root line is loaded
     * (again) for this page record. It walks up the rootLine up to
     * the first viewable record.
     *
     * While upon the first accessibility check of the root line it was done
     * by loading page by page from the page repository, this time the method
     * checkRootlineForIncludeSection() is used to find the most distant
     * accessible page within the root line.
     *
     * Having found the final page id, the page record and the root line are
     * loaded for last time by this method.
     *
     * Exceptions may be thrown for DOKTYPE_SPACER and not loadable page records
     * or root lines.
     *
     * @throws ServiceUnavailableException
     * @throws PageNotFoundException
     * @throws ShortcutTargetPageNotFoundException
     */
    protected function getPageAndRootline(ServerRequestInterface $request, PageInformation $pageInformation): void
    {
        $requestedPageRowWithoutGroupCheck = [];
        $id = $pageInformation->getId();
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $mountPoint = $pageInformation->getMountPoint();
        $pageRecord = $pageRepository->getPage($id);
        $pageInformation->setPageRecord($pageRecord);
        if (empty($pageRecord)) {
            // If no page, we try to find the page above in the rootLine.
            // Page is 'not found' in case the id itself was not an accessible page. code 1
            $this->pageNotFound = 1;
            $requestedPageIsHidden = false;
            try {
                $hiddenField = $GLOBALS['TCA']['pages']['ctrl']['enablecolumns']['disabled'] ?? '';
                $includeHiddenPages = $this->context->getPropertyFromAspect('visibility', 'includeHiddenPages') || $this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false);
                if (!empty($hiddenField) && !$includeHiddenPages) {
                    // Page is "hidden" => 404 (deliberately done in default language, as this cascades to language overlays)
                    $rawPageRecord = $pageRepository->getPage_noCheck($id);
                    // If page record could not be resolved throw exception
                    if ($rawPageRecord === []) {
                        $message = 'The requested page does not exist!';
                        try {
                            $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                                $request,
                                $message,
                                ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
                            );
                            throw new PropagateResponseException($response, 1674144383);
                        } catch (PageNotFoundException) {
                            throw new PageNotFoundException($message, 1674539331);
                        }
                    }
                    $requestedPageIsHidden = (bool)$rawPageRecord[$hiddenField];
                }
                $requestedPageRowWithoutGroupCheck = $pageRepository->getPage($id, true);
                if (!empty($requestedPageRowWithoutGroupCheck)) {
                    $this->pageAccessFailureHistory['direct_access'][] = $requestedPageRowWithoutGroupCheck;
                }
                $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $id, $mountPoint)->get();
                $pageInformation->setRootLine($rootLine);
                if (!empty($rootLine)) {
                    $c = count($rootLine) - 1;
                    while ($c > 0) {
                        // Add to page access failure history:
                        $this->pageAccessFailureHistory['direct_access'][] = $rootLine[$c];
                        // Decrease to next page in rootline and check the access to that, if OK, set as page record and ID value.
                        $c--;
                        $id = (int)$rootLine[$c]['uid'];
                        $pageInformation->setId($id);
                        $pageRecord = $pageRepository->getPage($id);
                        $pageInformation->setPageRecord($pageRecord);
                        if (!empty($pageRecord)) {
                            break;
                        }
                    }
                }
                unset($rootLine);
            } catch (RootLineException) {
                // @todo: Empty for now, $pageInformation->rootLine will stay empty array. *Maybe* the try-catch could
                //        be relocated around the RootlineUtility->get() call above, but it is currently unclear if
                //        ErrorController->pageNotFoundAction() may eventually throw such exceptions as well?
            }
            if ($requestedPageIsHidden || (empty($requestedPageRowWithoutGroupCheck) && empty($pageRecord))) {
                // Error out if there is still no page record!
                $message = 'The requested page does not exist!';
                try {
                    $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        $message,
                        $this->getPageAccessFailureReasons(PageAccessFailureReasons::PAGE_NOT_FOUND)
                    );
                    throw new PropagateResponseException($response, 1533931330);
                } catch (PageNotFoundException) {
                    throw new PageNotFoundException($message, 1301648780);
                }
            }
        }

        // Spacer and sysfolders are not accessible in frontend
        $pageDoktype = (int)($pageRecord['doktype'] ?? 0);
        $isSpacerOrSysfolder = $pageDoktype === PageRepository::DOKTYPE_SPACER || $pageDoktype === PageRepository::DOKTYPE_SYSFOLDER;
        // Page itself is not accessible, but the parent page is a spacer/sysfolder
        if ($isSpacerOrSysfolder && !empty($requestedPageRowWithoutGroupCheck)) {
            try {
                $response = GeneralUtility::makeInstance(ErrorController::class)->accessDeniedAction(
                    $request,
                    'Subsection was found and not accessible',
                    $this->getPageAccessFailureReasons(PageAccessFailureReasons::ACCESS_DENIED_SUBSECTION_NOT_RESOLVED)
                );
                throw new PropagateResponseException($response, 1633171038);
            } catch (PageNotFoundException) {
                throw new PageNotFoundException('Subsection was found and not accessible', 1633171172);
            }
        }
        if ($isSpacerOrSysfolder) {
            $message = 'The requested page does not exist!';
            try {
                $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                    $request,
                    $message,
                    $this->getPageAccessFailureReasons(PageAccessFailureReasons::ACCESS_DENIED_INVALID_PAGETYPE)
                );
                throw new PropagateResponseException($response, 1533931343);
            } catch (PageNotFoundException) {
                throw new PageNotFoundException($message, 1301648781);
            }
        }

        // Is the ID a link to another page?
        if ($pageDoktype === PageRepository::DOKTYPE_SHORTCUT) {
            // Clear mount point if page is a shortcut: If the shortcut goes to another page, we LEAVE the rootline which the MP expects.
            $mountPoint = '';
            $pageInformation->setMountPoint($mountPoint);
            // Saving the page so that we can check later - when we know about languages - whether we took the correct shortcut
            // or if a translation of the page overwrites the shortcut target, and we need to follow the new target.
            $pageInformation = $this->settingLanguage($request, $pageInformation);
            // Reset vars to new state that may have been created by settingLanguage()
            $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
            $pageRecord = $pageInformation->getPageRecord();
            $pageInformation->setOriginalShortcutPageRecord($pageRecord);
            $pageRecord = $pageRepository->resolveShortcutPage($pageRecord, true);
            $pageInformation->setPageRecord($pageRecord);
            $id = (int)$pageRecord['uid'];
            $pageInformation->setId($id);
            $pageDoktype = (int)($pageRecord['doktype'] ?? 0);
        }

        // If the page is a mount point which should be overlaid with the contents of the mounted page,
        // it must never be accessible directly, but only in the mount point context.
        // We thus change the current page id.
        if ($pageDoktype === PageRepository::DOKTYPE_MOUNTPOINT && $pageRecord['mount_pid_ol']) {
            $originalMountPointPageRecord = $pageRecord;
            $pageInformation->setOriginalMountPointPageRecord($pageRecord);
            $pageRecord = $pageRepository->getPage($originalMountPointPageRecord['mount_pid']);
            if (empty($pageRecord)) {
                $message = 'This page (ID ' . $originalMountPointPageRecord['uid'] . ') is of type "Mount point" and '
                    . 'mounts a page which is not accessible (ID ' . $originalMountPointPageRecord['mount_pid'] . ').';
                throw new PageNotFoundException($message, 1402043263);
            }
            $pageInformation->setPageRecord($pageRecord);
            // If the current page is a shortcut, the MP parameter will be replaced
            if ($mountPoint === '' || !empty($pageInformation->getOriginalShortcutPageRecord())) {
                $mountPoint = $pageRecord['uid'] . '-' . $originalMountPointPageRecord['uid'];
            } else {
                $mountPoint = $mountPoint . ',' . $pageRecord['uid'] . '-' . $originalMountPointPageRecord['uid'];
            }
            $pageInformation->setMountPoint($mountPoint);
            $id = (int)$pageRecord['uid'];
            $pageInformation->setId($id);
        }

        // Get rootLine and error out if it can not be retrieved.
        try {
            $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $id, $mountPoint)->get();
        } catch (RootLineException) {
            $rootLine = [];
        }
        if (empty($rootLine)) {
            $message = 'The requested page did not have a proper connection to the tree root!';
            $context = ['pageId' => $id];
            if (($normalizedParams = $request->getAttribute('normalizedParams')) instanceof NormalizedParams) {
                $context['requestUrl'] = $normalizedParams->getRequestUrl();
            }
            $this->logger->error($message, $context);
            try {
                $response = GeneralUtility::makeInstance(ErrorController::class)->internalErrorAction(
                    $request,
                    $message,
                    $this->getPageAccessFailureReasons(PageAccessFailureReasons::ROOTLINE_BROKEN)
                );
                throw new PropagateResponseException($response, 1533931350);
            } catch (AbstractServerErrorException $e) {
                $this->logger->error($message, ['exception' => $e]);
                $exceptionClass = get_class($e);
                throw new $exceptionClass($message, 1301648167);
            }
        }
        $pageInformation->setRootLine($rootLine);

        // Check for include section regarding hidden/starttime/endtime/fe_user - access control of a whole subbranch.
        $updatedRootLine = $this->checkRootlineForIncludeSection($pageInformation);
        if ($updatedRootLine !== null) {
            if (empty($updatedRootLine)) {
                $message = 'The requested page does not exist!';
                try {
                    $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        $message,
                        $this->getPageAccessFailureReasons(PageAccessFailureReasons::PAGE_NOT_FOUND)
                    );
                    throw new PropagateResponseException($response, 1533931351);
                } catch (AbstractServerErrorException $e) {
                    $this->logger->warning($message);
                    $exceptionClass = get_class($e);
                    throw new $exceptionClass($message, 1301648234);
                }
            }
            $el = reset($updatedRootLine);
            $id = (int)$el['uid'];
            $pageInformation->setId($id);
            $pageRecord = $pageRepository->getPage($id);
            $pageInformation->setPageRecord($pageRecord);
            try {
                $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $id, $mountPoint)->get();
            } catch (RootLineException) {
                $rootLine = [];
            }
            $pageInformation->setRootLine($rootLine);
        }
    }

    /**
     * If $this->pageNotFound is set, then throw an exception to stop further page generation process
     */
    protected function evaluatePageNotFound(int $pageNotFoundNumber, ServerRequestInterface $request): void
    {
        if (!$pageNotFoundNumber) {
            return;
        }
        $response = match ($pageNotFoundNumber) {
            1 => GeneralUtility::makeInstance(ErrorController::class)->accessDeniedAction(
                $request,
                'ID was not an accessible page',
                $this->getPageAccessFailureReasons(PageAccessFailureReasons::ACCESS_DENIED_PAGE_NOT_RESOLVED)
            ),
            2 => GeneralUtility::makeInstance(ErrorController::class)->accessDeniedAction(
                $request,
                'Subsection was found and not accessible',
                $this->getPageAccessFailureReasons(PageAccessFailureReasons::ACCESS_DENIED_SUBSECTION_NOT_RESOLVED)
            ),
            3 => GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'ID was outside the domain',
                $this->getPageAccessFailureReasons(PageAccessFailureReasons::ACCESS_DENIED_HOST_PAGE_MISMATCH)
            ),
            default => GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'Unspecified error',
                $this->getPageAccessFailureReasons()
            ),
        };
        throw new PropagateResponseException($response, 1533931329);
    }

    /**
     * Setting the language key that will be used by the current page.
     * In this function it should be checked, 1) that this language exists, 2) that a page_overlay_record
     * exists, and if not the default language, 0 (zero), should be set.
     *
     * May reset:
     * $pageInformation->pageRecord
     * $pageInformation->pageRepository
     * $pageInformation->rootLine
     */
    protected function settingLanguage(ServerRequestInterface $request, PageInformation $pageInformation): PageInformation
    {
        // Get values from site language
        $site = $request->getAttribute('site');
        $language = $request->getAttribute('language', $site->getDefaultLanguage());
        $languageAspect = LanguageAspectFactory::createFromSiteLanguage($language);
        $languageId = $languageAspect->getId();
        $languageContentId = $languageAspect->getContentId();

        $pageRecord = $pageInformation->getPageRecord();

        $pageTranslationVisibility = new PageTranslationVisibility((int)($pageRecord['l18n_cfg'] ?? 0));
        // If the incoming language is set to another language than default
        if ($languageAspect->getId() > 0) {
            // Request the translation for the requested language
            $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
            $olRec = $pageRepository->getPageOverlay($pageRecord, $languageAspect);
            $overlaidLanguageId = (int)($olRec['sys_language_uid'] ?? 0);
            if ($overlaidLanguageId !== $languageAspect->getId()) {
                // If requested translation is not available
                if ($pageTranslationVisibility->shouldHideTranslationIfNoTranslatedRecordExists()) {
                    $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        'Page is not available in the requested language.',
                        ['code' => PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE]
                    );
                    throw new PropagateResponseException($response, 1533931388);
                }
                switch ($languageAspect->getLegacyLanguageMode()) {
                    case 'strict':
                        $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                            $request,
                            'Page is not available in the requested language (strict).',
                            ['code' => PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE_STRICT_MODE]
                        );
                        throw new PropagateResponseException($response, 1533931395);
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
                                $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                                    $request,
                                    'Page is not available in the requested language (fallbacks did not apply).',
                                    ['code' => PageAccessFailureReasons::LANGUAGE_AND_FALLBACKS_NOT_AVAILABLE]
                                );
                                throw new PropagateResponseException($response, 1533931402);
                            }
                        }
                        break;
                    default:
                        // Default is that everything defaults to the default language...
                        $languageId = ($languageContentId = 0);
                }
            }

            // Define the language aspect again now
            $languageAspect = GeneralUtility::makeInstance(
                LanguageAspect::class,
                $languageId,
                $languageContentId,
                $languageAspect->getOverlayType(),
                $languageAspect->getFallbackChain()
            );

            // Setting localized page record if an overlay record was found (which it is only if a language is used)
            // Doing this ensures that page properties like the page title are resolved in the correct language
            $pageInformation->setPageRecord($olRec);
        }

        // Set the language aspect
        $this->context->setAspect('language', $languageAspect);
        // If default language is not available
        if ((!$languageAspect->getContentId() || !$languageAspect->getId())
            && $pageTranslationVisibility->shouldBeHiddenInDefaultLanguage()
        ) {
            $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'Page is not available in default language.',
                ['code' => PageAccessFailureReasons::LANGUAGE_DEFAULT_NOT_AVAILABLE]
            );
            throw new PropagateResponseException($response, 1533931423);
        }

        if ($languageAspect->getId() > 0) {
            // Updating rootLine with translations if the language key is set.
            try {
                $pageInformation->setRootLine(GeneralUtility::makeInstance(
                    RootlineUtility::class,
                    $pageInformation->getId(),
                    $pageInformation->getMountPoint()
                )->get());
            } catch (RootLineException) {
                $pageInformation->setRootLine([]);
            }
        }

        return $pageInformation;
    }

    /**
     * Check the value of "content_from_pid" of the current page record, and see if the current request
     * should actually show content from another page.
     *
     * @return int the current page ID or another one if resolved properly
     */
    protected function resolveContentPid(ServerRequestInterface $request, PageInformation $pageInformation): int
    {
        $pageRecord = $pageInformation->getPageRecord();
        if (empty($pageRecord['content_from_pid'])) {
            return $pageInformation->getId();
        }
        // @todo: This does *not* re-init $pageInformation->pageRepository.
        //        It is currently unclear if this has positive or negative side effects!
        $pageInformation = clone $pageInformation;
        // Set id to the content_from_pid value - we are going to evaluate this pid as if it was a given id for a page-display.
        $pageInformation->setId($pageRecord['content_from_pid']);
        $pageInformation->setMountPoint('');
        $this->getPageAndRootline($request, $pageInformation);
        return $pageInformation->getId();
    }

    /**
     * Analysing $this->pageAccessFailureHistory into a summary array telling which features disabled display and on which pages and conditions.
     * That data can be used inside a page-not-found handler
     *
     * @param string|null $failureReasonCode the error code to be attached (optional), see PageAccessFailureReasons list for details
     * @return array Summary of why page access was not allowed.
     */
    protected function getPageAccessFailureReasons(string $failureReasonCode = null): array
    {
        $output = [];
        if ($failureReasonCode) {
            $output['code'] = $failureReasonCode;
        }
        $combinedRecords = array_merge(
            is_array($this->pageAccessFailureHistory['direct_access'] ?? false) ? $this->pageAccessFailureHistory['direct_access'] : [['fe_group' => 0]],
            is_array($this->pageAccessFailureHistory['sub_section'] ?? false) ? $this->pageAccessFailureHistory['sub_section'] : []
        );
        if (!empty($combinedRecords)) {
            $accessVoter = GeneralUtility::makeInstance(RecordAccessVoter::class);
            foreach ($combinedRecords as $k => $pagerec) {
                // If $k=0 then it is the very first page the original ID was pointing at and that will get a full check of course
                // If $k>0 it is parent pages being tested. They are only significant for the access to the first page IF they had the
                // extendToSubpages flag set, hence checked only then!
                if (!$k || $pagerec['extendToSubpages']) {
                    if ($pagerec['hidden'] ?? false) {
                        $output['hidden'][$pagerec['uid']] = true;
                    }
                    if (isset($pagerec['starttime']) && $pagerec['starttime'] > $GLOBALS['SIM_ACCESS_TIME']) {
                        $output['starttime'][$pagerec['uid']] = $pagerec['starttime'];
                    }
                    if (isset($pagerec['endtime']) && $pagerec['endtime'] != 0 && $pagerec['endtime'] <= $GLOBALS['SIM_ACCESS_TIME']) {
                        $output['endtime'][$pagerec['uid']] = $pagerec['endtime'];
                    }
                    if (!$accessVoter->groupAccessGranted('pages', $pagerec, $this->context)) {
                        $output['fe_group'][$pagerec['uid']] = $pagerec['fe_group'];
                    }
                }
            }
        }
        return $output;
    }

    /**
     * Checks if visibility of the page is blocked upwards in the root line.
     *
     * If any page in the root line is blocking visibility, true is returned.
     *
     * All pages from the blocking page downwards are removed from the root
     * line, so that the remaining pages can be used to relocate the page up
     * to the lowest visible page.
     *
     * The blocking feature of a page must be turned on by setting the page
     * record field 'extendToSubpages' to 1 in case of hidden, starttime,
     * endtime or fe_group restrictions.
     *
     * Additionally, this method checks for backend user sections in root line
     * and if found, evaluates if a backend user is logged in and has access.
     */
    protected function checkRootlineForIncludeSection(PageInformation $pageInformation): ?array
    {
        $rootLine = $pageInformation->getRootLine();
        $pageRecord = $pageInformation->getPageRecord();
        $c = count($rootLine);
        $removeTheRestFlag = false;
        $accessVoter = GeneralUtility::makeInstance(RecordAccessVoter::class);
        for ($a = 0; $a < $c; $a++) {
            if (!$accessVoter->accessGrantedForPageInRootLine($rootLine[$a], $this->context)) {
                // Add to page access failure history and mark the page as not found.
                // Keep the rootLine however to trigger access denied error instead of a service unavailable error
                $this->pageAccessFailureHistory['sub_section'][] = $rootLine[$a];
                $this->pageNotFound = 2;
            }
            if ((int)$rootLine[$a]['doktype'] === PageRepository::DOKTYPE_BE_USER_SECTION) {
                // If there is a backend user logged in, check if they have read access to the page
                if ($this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false)) {
                    // If there was no page selected, the user apparently did not have read access to the
                    // current page (not position in rootLine) and we set the remove-flag...
                    if (!$this->getBackendUser()->doesUserHaveAccess($pageRecord, Permission::PAGE_SHOW)) {
                        $removeTheRestFlag = true;
                    }
                } else {
                    // Don't go here, if there is no backend user logged in.
                    $removeTheRestFlag = true;
                }
            }
            if ($removeTheRestFlag) {
                // Page is 'not found' in case a subsection was found and not accessible, code 2
                $this->pageNotFound = 2;
                unset($rootLine[$a]);
            }
        }
        if ($removeTheRestFlag) {
            return $rootLine;
        }
        return null;
    }

    protected function getBackendUser(): ?FrontendBackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
