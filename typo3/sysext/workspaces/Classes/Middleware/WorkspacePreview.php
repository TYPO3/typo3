<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Workspaces\Middleware;

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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\CookieHeaderTrait;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Workspaces\Authentication\PreviewUserAuthentication;

/**
 * Middleware to
 * - evaluate ADMCMD_prev as GET parameter or from a cookie
 * - initializes the PreviewUser as $GLOBALS['BE_USER']
 * - renders a message about a possible workspace previewing currently
 *
 * @internal
 */
class WorkspacePreview implements MiddlewareInterface
{
    use CookieHeaderTrait;

    /**
     * The GET parameter to be used (also the cookie name)
     *
     * @var string
     */
    protected $previewKey = 'ADMCMD_prev';

    /**
     * Initializes a possible preview user (by checking for GET/cookie of name "ADMCMD_prev")
     *
     * The GET parameter "ADMCMD_noBeUser" can be used to preview a live workspace from the backend even if the
     * backend user is in a different workspace.
     *
     * Additionally, if a workspace is previewed, an additional message text is shown.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $addInformationAboutDisabledCache = false;
        $keyword = $this->getPreviewInputCode($request);
        if ($keyword) {
            switch ($keyword) {
                case 'IGNORE':
                    break;
                case 'LOGOUT':
                    // "log out", and unset the cookie
                    $this->setCookie('', $request->getAttribute('normalizedParams'));
                    $message = $this->getLogoutTemplateMessage($request->getQueryParams()['returnUrl'] ?? '');
                    return new HtmlResponse($message);
                default:
                    $pageArguments = $request->getAttribute('routing', null);
                    // A keyword was found in a query parameter or in a cookie
                    // If the keyword is valid, activate a BE User and override any existing BE Users
                    $configuration = $this->getPreviewConfigurationFromRequest($request, $keyword);
                    if (is_array($configuration) && $configuration['fullWorkspace'] > 0 && $pageArguments instanceof PageArguments) {
                        $previewUser = $this->initializePreviewUser(
                            (int)$configuration['fullWorkspace'],
                            $pageArguments->getPageId()
                        );
                        if ($previewUser) {
                            $GLOBALS['BE_USER'] = $previewUser;
                            // Register the preview user as aspect
                            $this->setBackendUserAspect(GeneralUtility::makeInstance(Context::class), $previewUser);
                        }
                    }
            }
        }

        // If "ADMCMD_noBeUser" is set, then ensure that there is no workspace preview and no BE User logged in.
        // This option is solely used to ensure that a be user can preview the live version of a page in the
        // workspace preview module.
        if ($request->getQueryParams()['ADMCMD_noBeUser']) {
            $GLOBALS['BE_USER'] = null;
            // Register the backend user as aspect
            $this->setBackendUserAspect(GeneralUtility::makeInstance(Context::class), null);
            // Caching is disabled, because otherwise generated URLs could include the ADMCMD_noBeUser parameter
            $request = $request->withAttribute('noCache', true);
            $addInformationAboutDisabledCache = true;
        }

        $response = $handler->handle($request);

        // Caching is disabled, because otherwise generated URLs could include the ADMCMD_noBeUser parameter
        if ($addInformationAboutDisabledCache) {
            $GLOBALS['TSFE']->set_no_cache('GET Parameter ADMCMD_noBeUser was given', true);
        }

        // Add an info box to the frontend content
        if ($GLOBALS['TSFE']->doWorkspacePreview() && $GLOBALS['TSFE']->isOutputting()) {
            $previewInfo = $this->renderPreviewInfo($GLOBALS['TSFE'], $request->getAttribute('normalizedParams'));
            $body = $response->getBody();
            $body->rewind();
            $content = $body->getContents();
            $content = str_ireplace('</body>', $previewInfo . '</body>', $content);
            $body = new Stream('php://temp', 'rw');
            $body->write($content);
            $response = $response->withBody($body);
        }

        return $response;
    }

    /**
     * Renders the logout template when the "logout" button was pressed.
     * Returns a string which can be put into a HttpResponse.
     *
     * @param string $returnUrl
     * @return string
     */
    protected function getLogoutTemplateMessage(string $returnUrl = ''): string
    {
        $returnUrl = GeneralUtility::sanitizeLocalUrl($returnUrl);
        $returnUrl = $this->removePreviewParameterFromUrl($returnUrl);
        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['workspacePreviewLogoutTemplate']) {
            $templateFile = GeneralUtility::getFileAbsFileName($GLOBALS['TYPO3_CONF_VARS']['FE']['workspacePreviewLogoutTemplate']);
            if (@is_file($templateFile)) {
                $message = file_get_contents($templateFile);
            } else {
                $message = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:previewLogoutError');
                $message = htmlspecialchars($message);
                $message = sprintf($message, '<strong>', '</strong><br>', $templateFile);
            }
        } else {
            $message = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:previewLogoutSuccess');
            $message = htmlspecialchars($message);
            $message = sprintf($message, '<a href="' . htmlspecialchars($returnUrl) . '">', '</a>');
        }
        return sprintf($message, htmlspecialchars($returnUrl));
    }

    /**
     * Looking for an ADMCMD_prev code, looks it up if found and returns configuration data.
     * Background: From the backend a request to the frontend to show a page, possibly with
     * workspace preview can be "recorded" and associated with a keyword.
     * When the frontend is requested with this keyword the associated request parameters are
     * restored from the database AND the backend user is loaded - only for that request.
     * The main point is that a special URL valid for a limited time,
     * eg. http://localhost/typo3site/index.php?ADMCMD_prev=035d9bf938bd23cb657735f68a8cedbf will
     * open up for a preview that doesn't require login. Thus it's useful for sending in an email
     * to someone without backend account.
     *
     * @param ServerRequestInterface $request
     * @param string $inputCode
     * @return array Preview configuration array from sys_preview record.
     * @throws \Exception
     */
    protected function getPreviewConfigurationFromRequest(ServerRequestInterface $request, string $inputCode): array
    {
        $previewData = $this->getPreviewData($inputCode);
        if (!is_array($previewData)) {
            throw new \Exception('ADMCMD command could not be executed! (No keyword configuration found)', 1294585192);
        }
        if ($request->getMethod() === 'POST') {
            throw new \Exception('POST requests are incompatible with keyword preview.', 1294585191);
        }
        // Validate configuration
        $previewConfig = json_decode($previewData['config'], true);
        if (!$previewConfig['fullWorkspace']) {
            throw new \Exception('Preview configuration did not include a workspace preview', 1294585190);
        }
        // If the GET parameter ADMCMD_prev is set, then a cookie is set for the next request
        if ($request->getQueryParams()[$this->previewKey] ?? false) {
            $this->setCookie($inputCode, $request->getAttribute('normalizedParams'));
        }
        return $previewConfig;
    }

    /**
     * Creates a preview user and sets the workspace ID and the current page ID (for accessing the page)
     *
     * @param int $workspaceUid the workspace ID to set
     * @param mixed $requestedPageId pageID to the current page
     * @return PreviewUserAuthentication|bool if the set up of the workspace was successful, the user is returned.
     */
    protected function initializePreviewUser(int $workspaceUid, $requestedPageId)
    {
        if ($workspaceUid > 0) {
            $previewUser = GeneralUtility::makeInstance(PreviewUserAuthentication::class);
            $previewUser->setWebmounts([$requestedPageId]);
            if ($previewUser->setTemporaryWorkspace($workspaceUid)) {
                return $previewUser;
            }
        }
        return false;
    }

    /**
     * Sets a cookie for logging in a preview user
     *
     * @param string $inputCode
     * @param NormalizedParams $normalizedParams
     */
    protected function setCookie(string $inputCode, NormalizedParams $normalizedParams)
    {
        $cookieSameSite = $this->sanitizeSameSiteCookieValue(
            strtolower($GLOBALS['TYPO3_CONF_VARS']['BE']['cookieSameSite'] ?? Cookie::SAMESITE_STRICT)
        );
        // None needs the secure option (only allowed on HTTPS)
        $cookieSecure = $cookieSameSite === Cookie::SAMESITE_NONE || $normalizedParams->isHttps();

        $cookie = new Cookie(
            $this->previewKey,
            $inputCode,
            0,
            $normalizedParams->getSitePath(),
            null,
            $cookieSecure,
            true,
            false,
            $cookieSameSite
        );
        header('Set-Cookie: ' . $cookie->__toString(), false);
    }

    /**
     * Returns the input code value from the admin command variable
     * If no inputcode and a cookie is set, load input code from cookie
     *
     * @param ServerRequestInterface $request
     * @return string keyword
     */
    protected function getPreviewInputCode(ServerRequestInterface $request): string
    {
        return $request->getQueryParams()[$this->previewKey] ?? $request->getCookieParams()[$this->previewKey] ?? '';
    }

    /**
     * Look for keyword configuration record in the database, but check if the keyword has expired already
     *
     * @param string $keyword
     * @return mixed array of the result set or null
     */
    protected function getPreviewData(string $keyword)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_preview');
        return $queryBuilder
            ->select('*')
            ->from('sys_preview')
            ->where(
                $queryBuilder->expr()->eq(
                    'keyword',
                    $queryBuilder->createNamedParameter($keyword)
                ),
                $queryBuilder->expr()->gt(
                    'endtime',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], \PDO::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch();
    }

    /**
     * Code regarding adding a custom preview message, when previewing a workspace
     */

    /**
     * Renders a message at the bottom of the HTML page, can be modified via
     *
     *   config.disablePreviewNotification = 1 (to disable the additional info text)
     *
     * and
     *
     *   config.message_preview_workspace = This is not the online version but the version of "%s" workspace (ID: %s).
     *
     * via TypoScript.
     *
     * @param TypoScriptFrontendController $tsfe
     * @param NormalizedParams $normalizedParams
     * @return string
     */
    protected function renderPreviewInfo(TypoScriptFrontendController $tsfe, NormalizedParams $normalizedParams): string
    {
        $content = '';
        if (!isset($tsfe->config['config']['disablePreviewNotification']) || (int)$tsfe->config['config']['disablePreviewNotification'] !== 1) {
            // get the title of the current workspace
            $currentWorkspaceId = $tsfe->whichWorkspace();
            $currentWorkspaceTitle = $this->getWorkspaceTitle($currentWorkspaceId);
            $currentWorkspaceTitle = htmlspecialchars($currentWorkspaceTitle);
            if ($tsfe->config['config']['message_preview_workspace']) {
                $content = sprintf(
                    $tsfe->config['config']['message_preview_workspace'],
                    $currentWorkspaceTitle,
                    $currentWorkspaceId ?? -99
                );
            } else {
                $text = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:previewText');
                $text = htmlspecialchars($text);
                $text = sprintf($text, $currentWorkspaceTitle, $currentWorkspaceId ?? -99);
                $stopPreviewText = $this->getLanguageService()->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:stopPreview');
                $stopPreviewText = htmlspecialchars($stopPreviewText);
                if ($GLOBALS['BE_USER'] instanceof PreviewUserAuthentication) {
                    $url = $this->removePreviewParameterFromUrl($normalizedParams->getRequestUri());
                    $urlForStoppingPreview = $normalizedParams->getSiteUrl() . 'index.php?returnUrl=' . rawurlencode($url) . '&ADMCMD_prev=LOGOUT';
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
     *
     * @param $workspaceId
     * @return string the title of the workspace
     */
    protected function getWorkspaceTitle(int $workspaceId): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_workspace');
        $title = $queryBuilder
            ->select('title')
            ->from('sys_workspace')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($workspaceId, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn();
        return (string)($title !== false ? $title : '');
    }

    /**
     * Used for generating URLs (e.g. in logout page) without the existing ADMCMD_prev keyword as GET variable
     *
     * @param string $url
     * @return string
     */
    protected function removePreviewParameterFromUrl(string $url): string
    {
        return (string)preg_replace('/\\&?' . $this->previewKey . '=[[:alnum:]]+/', '', $url);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'] ?: GeneralUtility::makeInstance(LanguageService::class);
    }

    /**
     * Register the backend user as aspect
     *
     * @param Context $context
     * @param BackendUserAuthentication $user
     */
    protected function setBackendUserAspect(Context $context, BackendUserAuthentication $user = null)
    {
        $context->setAspect('backend.user', GeneralUtility::makeInstance(UserAspect::class, $user));
        $context->setAspect('workspace', GeneralUtility::makeInstance(WorkspaceAspect::class, $user ? $user->workspace : 0));
    }
}
