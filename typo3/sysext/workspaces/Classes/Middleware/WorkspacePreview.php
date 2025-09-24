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

namespace TYPO3\CMS\Workspaces\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\CookieHeaderTrait;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Routing\RouteResultInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendBackendUserAuthentication;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\Event\AfterTypoScriptDeterminedEvent;
use TYPO3\CMS\Workspaces\Authentication\PreviewUserAuthentication;

/**
 * Middleware to
 * - evaluate ADMCMD_prev as GET parameter or from a cookie
 * - initializes the PreviewUser as $GLOBALS['BE_USER']
 * - renders a message about a possible workspace previewing currently
 *
 * @internal
 */
final class WorkspacePreview implements MiddlewareInterface
{
    use CookieHeaderTrait;

    /**
     * The GET parameter to be used (also the cookie name)
     */
    private const PREVIEW_KEY = 'ADMCMD_prev';

    private bool $previewNotificationEnabled = false;
    private ?string $previewMessage = null;

    public function __construct(private readonly Context $context) {}

    /**
     * Initializes a possible preview user (by checking for GET/cookie of name "ADMCMD_prev")
     *
     * The GET parameter "ADMCMD_prev=LIVE" can be used to preview a live workspace from the backend even if the
     * backend user is in a different workspace.
     *
     * Additionally, if a workspace is previewed, an additional message text is shown.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $keyword = $this->getPreviewInputCode($request);
        $setCookieOnCurrentRequest = false;
        $normalizedParams = $request->getAttribute('normalizedParams');

        // First, if a Log-out is happening, a custom HTML output page is shown and the request exits with removing
        // the cookie for the backend preview.
        if ($keyword === 'LOGOUT') {
            // "log out", and unset the cookie
            $message = $this->getLogoutTemplateMessage($request->getUri());
            $response = new HtmlResponse($message);
            return $this->addCookie('', $normalizedParams, $response);
        }

        // If the keyword is "IGNORE", then the preview is not managed as "Preview User" but handled
        // via the regular backend user or even no user if the GET parameter ADMCMD_noBeUser is set
        if (!empty($keyword) && $keyword !== 'IGNORE' && $keyword !== 'LIVE') {
            $routeResult = $request->getAttribute('routing', null);
            // A keyword was found in a query parameter or in a cookie
            // If the keyword is valid, activate a BE User and override any existing BE Users
            // (in case workspace ID was given and a corresponding site to be used was found)
            $previewWorkspaceId = (int)$this->getWorkspaceIdFromRequest($keyword);
            if ($previewWorkspaceId > 0 && $routeResult instanceof RouteResultInterface) {
                $previewUser = $this->initializePreviewUser($previewWorkspaceId);
                if ($previewUser !== null) {
                    $GLOBALS['BE_USER'] = $previewUser;
                    // Register the preview user as aspect
                    $this->setBackendUserAspect($previewUser);
                    // If the GET parameter is set, and we have a valid Preview User, the cookie needs to be
                    // set and the GET parameter should be removed.
                    $setCookieOnCurrentRequest = $request->getQueryParams()[self::PREVIEW_KEY] ?? false;
                }
            }
        }

        // Will be overridden by the PreviewSimulator if needed, but at least hidden/scheduled pages are shown
        // when a workspace preview is active. This can be removed once PageResolverMiddleware does not
        // consider the visibility aspect anymore.
        if ($this->context->getPropertyFromAspect('workspace', 'isOffline', false)) {
            $this->context->setAspect('visibility', new VisibilityAspect(true, false, false, true));
        }

        // If keyword is set to "LIVE", then ensure that there is no workspace preview, but keep the BE User logged in.
        // This option is solely used to ensure that a be-user can preview the live version of a page in the
        // workspace preview module.
        if ($keyword === 'LIVE' && isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER'] instanceof FrontendBackendUserAuthentication) {
            // We need to set the workspace to "live" here
            $GLOBALS['BE_USER']->setTemporaryWorkspace(0);
            // Register the backend user as aspect
            $this->setBackendUserAspect($GLOBALS['BE_USER']);
            $cacheInstruction = $request->getAttribute('frontend.cache.instruction', new CacheInstruction());
            $cacheInstruction->disableCache('EXT:workspaces: Disabled FE cache with BE_USER previewing live workspace');
            $request = $request->withAttribute('frontend.cache.instruction', $cacheInstruction);
            $setCookieOnCurrentRequest = false;
        }

        $response = $handler->handle($request);

        // Add an info box to the frontend content
        if ($this->context->getPropertyFromAspect('workspace', 'isOffline', false)) {
            $previewInfo = $this->renderPreviewInfo($request->getUri());
            $body = $response->getBody();
            $body->rewind();
            $content = $body->getContents();
            $content = str_ireplace('</body>', $previewInfo . '</body>', $content);
            $body = new Stream('php://temp', 'rw');
            $body->write($content);
            $response = $response->withBody($body);
        }

        // If the GET parameter ADMCMD_prev is set, then a cookie is set for the next request to keep the preview user
        if ($setCookieOnCurrentRequest) {
            $response = $this->addCookie($keyword, $normalizedParams, $response);
        }
        return $response;
    }

    /**
     * This middleware is run pretty early in the FE chain to initialize correctly.
     * To render the preview information however, it depends on TypoScript 'config',
     * which is not available in the incoming Request, yet.
     * It thus listens on the AfterTypoScriptDeterminedEvent to determine its preview
     * details.
     */
    #[AsEventListener('typo3-workspaces/workspace-preview-middleware')]
    public function typoScriptDeterminedListener(AfterTypoScriptDeterminedEvent $event): void
    {
        $typoScriptConfig = $event->getFrontendTypoScript()->getConfigArray();
        if (!isset($typoScriptConfig['disablePreviewNotification']) || (int)$typoScriptConfig['disablePreviewNotification'] !== 1) {
            $this->previewNotificationEnabled = true;
        }
        if ($typoScriptConfig['message_preview_workspace'] ?? false) {
            $this->previewMessage = $typoScriptConfig['message_preview_workspace'];
        }
    }

    /**
     * Renders the logout template when the "logout" button was pressed.
     * Returns a string which can be put into a HttpResponse.
     */
    private function getLogoutTemplateMessage(UriInterface $currentUrl): string
    {
        $currentUrl = $this->removePreviewParameterFromUrl($currentUrl);
        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['workspacePreviewLogoutTemplate']) {
            $templateFile = GeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['FE']['workspacePreviewLogoutTemplate']);
            if (@is_file($templateFile)) {
                $message = (string)file_get_contents($templateFile);
            } else {
                $message = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:previewLogoutError');
                $message = htmlspecialchars($message);
                $message = sprintf($message, '<strong>', '</strong><br>', $templateFile);
            }
        } else {
            $message = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:previewLogoutSuccess');
            $message = htmlspecialchars($message);
            $message = sprintf($message, '<a href="' . htmlspecialchars((string)$currentUrl) . '">', '</a>');
        }
        return sprintf($message, htmlspecialchars((string)$currentUrl));
    }

    /**
     * Looking for an ADMCMD_prev code, looks it up if found and returns configuration data.
     * Background: From the backend a request to the frontend to show a page, possibly with
     * workspace preview can be "recorded" and associated with a keyword.
     * When the frontend is requested with this keyword the associated request parameters are
     * restored from the database AND the backend user is loaded - only for that request.
     * The main point is that a special URL valid for a limited time,
     * e.g. http://localhost/typo3site/index.php?ADMCMD_prev=035d9bf938bd23cb657735f68a8cedbf will
     * open up for a preview that doesn't require login. Thus, it's useful for sending in an email
     * to someone without backend account.
     *
     * @return int|null Workspace ID stored in the preview configuration array of a sys_preview record.
     * @throws \Exception
     */
    private function getWorkspaceIdFromRequest(string $inputCode): ?int
    {
        $previewData = $this->getPreviewData($inputCode);
        if (!is_array($previewData)) {
            // ADMCMD command could not be executed! (No keyword configuration found)
            return null;
        }
        // Validate configuration
        $previewConfig = json_decode($previewData['config'] ?? '', true);
        if (!isset($previewConfig['fullWorkspace']) || !$previewConfig['fullWorkspace']) {
            throw new \Exception('Preview configuration did not include a workspace preview', 1294585190);
        }
        return (int)$previewConfig['fullWorkspace'];
    }

    /**
     * Creates a preview user and sets the workspace ID
     *
     * @param int $workspaceUid the workspace ID to set
     * @return PreviewUserAuthentication|null if the set up of the workspace was successful, the user is returned.
     */
    private function initializePreviewUser(int $workspaceUid): ?PreviewUserAuthentication
    {
        $previewUser = GeneralUtility::makeInstance(PreviewUserAuthentication::class);
        if ($previewUser->setTemporaryWorkspace($workspaceUid)) {
            return $previewUser;
        }
        return null;
    }

    /**
     * Adds a cookie for logging in a preview user into the HTTP response
     */
    private function addCookie(string $keyword, NormalizedParams $normalizedParams, ResponseInterface $response): ResponseInterface
    {
        $cookieSameSite = $this->sanitizeSameSiteCookieValue(
            strtolower($GLOBALS['TYPO3_CONF_VARS']['BE']['cookieSameSite'] ?? Cookie::SAMESITE_STRICT)
        );
        // SameSite Cookie = None needs the secure option (only allowed on HTTPS)
        $isSecure = $cookieSameSite === Cookie::SAMESITE_NONE || $normalizedParams->isHttps();

        $cookie = new Cookie(
            self::PREVIEW_KEY,
            $keyword,
            0,
            $normalizedParams->getSitePath(),
            null,
            $isSecure,
            true,
            false,
            $cookieSameSite
        );
        return $response->withAddedHeader('Set-Cookie', $cookie->__toString());
    }

    /**
     * Returns the input code value from the admin command variable
     * If no input code and a cookie is set, load input code from cookie
     */
    private function getPreviewInputCode(ServerRequestInterface $request): string
    {
        return $request->getQueryParams()[self::PREVIEW_KEY] ?? $request->getCookieParams()[self::PREVIEW_KEY] ?? '';
    }

    /**
     * Look for keyword configuration record in the database, but check if the keyword has expired already
     *
     * @return mixed array of the result set or null
     */
    private function getPreviewData(string $keyword)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_preview');
        return $queryBuilder
            ->select('*')
            ->from('sys_preview')
            ->where(
                $queryBuilder->expr()->eq('keyword', $queryBuilder->createNamedParameter($keyword)),
                $queryBuilder->expr()->gt('endtime', $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], Connection::PARAM_INT))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
    }

    /**
     * Renders a message at the bottom of the HTML page, can be modified via
     *   config.disablePreviewNotification = 1 (to disable the additional info text)
     * and
     *   config.message_preview_workspace = This is not the online version but the version of "%s" workspace (ID: %s).
     * via TypoScript.
     */
    private function renderPreviewInfo(UriInterface $currentUrl): string
    {
        $content = '';
        if ($this->previewNotificationEnabled) {
            // get the title of the current workspace
            $currentWorkspaceId = $this->context->getPropertyFromAspect('workspace', 'id', 0);
            $currentWorkspaceTitle = $this->getWorkspaceTitle($currentWorkspaceId);
            $currentWorkspaceTitle = htmlspecialchars($currentWorkspaceTitle);
            if ($this->previewMessage !== null) {
                $content = sprintf(
                    $this->previewMessage,
                    $currentWorkspaceTitle,
                    $currentWorkspaceId
                );
            } else {
                $text = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:previewText');
                $text = htmlspecialchars($text);
                $text = sprintf($text, $currentWorkspaceTitle, $currentWorkspaceId);
                $stopPreviewText = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stopPreview');
                $stopPreviewText = htmlspecialchars($stopPreviewText);
                if ($GLOBALS['BE_USER'] instanceof PreviewUserAuthentication) {
                    $urlForStoppingPreview = (string)$this->removePreviewParameterFromUrl($currentUrl, 'LOGOUT');
                    $text .= '<br><a style="color: #000; pointer-events: visible;" href="' . htmlspecialchars($urlForStoppingPreview) . '">' . $stopPreviewText . '</a>';
                }
                $styles = [];
                $styles[] = 'position: fixed';
                $styles[] = 'top: 15px';
                $styles[] = 'right: 15px';
                $styles[] = 'padding: 8px 18px';
                $styles[] = 'background: #fff3cd';
                $styles[] = 'border: 1px solid #ffeeba';
                $styles[] = 'font-family: sans-serif';
                $styles[] = 'font-size: 14px';
                $styles[] = 'font-weight: bold';
                $styles[] = 'color: #856404';
                $styles[] = 'z-index: 20000';
                $styles[] = 'user-select: none';
                $styles[] = 'pointer-events: none';
                $styles[] = 'text-align: center';
                $styles[] = 'border-radius: 2px';
                $content = '<div id="typo3-preview-info" style="' . implode(';', $styles) . '">' . $text . '</div>';
            }
        }
        return $content;
    }

    /**
     * Fetches the title of the workspace
     */
    private function getWorkspaceTitle(int $workspaceId): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_workspace');
        $title = $queryBuilder
            ->select('title')
            ->from('sys_workspace')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($workspaceId, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();
        return (string)($title !== false ? $title : '');
    }

    /**
     * Used for generating URLs (e.g. in logout page) without the existing ADMCMD_prev keyword as GET variable
     */
    private function removePreviewParameterFromUrl(UriInterface $url, string $newAdminCommand = ''): UriInterface
    {
        $queryString = $url->getQuery();
        if (!empty($queryString)) {
            $queryStringParts = GeneralUtility::explodeUrl2Array($queryString);
            unset($queryStringParts[self::PREVIEW_KEY]);
        } else {
            $queryStringParts = [];
        }
        if ($newAdminCommand !== '') {
            $queryStringParts[self::PREVIEW_KEY] = $newAdminCommand;
        }
        $queryString = http_build_query($queryStringParts, '', '&', PHP_QUERY_RFC3986);
        return $url->withQuery($queryString);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'] ?? GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
    }

    /**
     * Register or override the backend user as aspect, as well as the workspace information the user object is holding
     */
    private function setBackendUserAspect(?BackendUserAuthentication $user = null): void
    {
        $this->context->setAspect('backend.user', new UserAspect($user));
        $this->context->setAspect('workspace', new WorkspaceAspect($user ? $user->workspace : 0));
    }
}
