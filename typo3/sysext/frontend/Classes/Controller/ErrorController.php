<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Controller;

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
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerNotConfiguredException;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handles "Page Not Found" or "Page Unavailable" requests,
 * returns a response object.
 */
class ErrorController
{
    /**
     * Used for creating a 500 response ("Page unavailable"), usually due some misconfiguration
     * but if configured, a RedirectResponse could be returned as well.
     *
     * @param ServerRequestInterface $request
     * @param string $message
     * @param array $reasons
     * @return ResponseInterface
     * @throws ServiceUnavailableException
     */
    public function unavailableAction(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        if (!$this->isPageUnavailableHandlerConfigured()) {
            throw new ServiceUnavailableException($message, 1518472181);
        }
        $errorHandler = $this->getErrorHandlerFromSite($request, 500);
        if ($errorHandler instanceof PageErrorHandlerInterface) {
            $response = $errorHandler->handlePageError($request, $message, $reasons);
            return $response->withStatus(500, $message);
        }
        return $this->handlePageError(
            $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling'],
            $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling_statheader'],
            $message,
            $reasons
        );
    }

    /**
     * Used for creating a 404 response ("Page Not Found"),
     * but if configured, a RedirectResponse could be returned as well.
     *
     * @param ServerRequestInterface $request
     * @param string $message
     * @param array $reasons
     * @return ResponseInterface
     * @throws PageNotFoundException
     */
    public function pageNotFoundAction(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        $errorHandler = $this->getErrorHandlerFromSite($request, 404);
        if ($errorHandler instanceof PageErrorHandlerInterface) {
            $response = $errorHandler->handlePageError($request, $message, $reasons);
            return $response->withStatus(404, $message);
        }
        if (!$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling']) {
            throw new PageNotFoundException($message, 1518472189);
        }
        return $this->handlePageError(
            $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'],
            $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling_statheader'],
            $message,
            $reasons
        );
    }

    /**
     * Used for creating a 403 response ("Access denied"),
     * but if configured, a RedirectResponse could be returned as well.
     *
     * @param ServerRequestInterface $request
     * @param string $message
     * @param array $reasons
     * @return ResponseInterface
     */
    public function accessDeniedAction(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        $errorHandler = $this->getErrorHandlerFromSite($request, 403);
        if ($errorHandler instanceof PageErrorHandlerInterface) {
            $response = $errorHandler->handlePageError($request, $message, $reasons);
            return $response->withStatus(403, $message);
        }
        return $this->handlePageError(
            $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'],
            $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling_accessdeniedheader'],
            $message,
            $reasons
        );
    }

    /**
     * Checks whether the pageUnavailableHandler should be used. To be used, pageUnavailable_handling must be set
     * and devIPMask must not match the current visitor's IP address.
     *
     * @return bool TRUE/FALSE whether the pageUnavailable_handler should be used.
     */
    protected function isPageUnavailableHandlerConfigured(): bool
    {
        return
            $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling']
            && !GeneralUtility::cmpIP(
                GeneralUtility::getIndpEnv('REMOTE_ADDR'),
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']
            )
        ;
    }

    /**
     * Generic error page handler.
     *
     * @param mixed $errorHandler See docs of ['FE']['pageNotFound_handling'] and ['FE']['pageUnavailable_handling'] for all possible values
     * @param string $header If set, this is passed directly to the PHP function, header()
     * @param string $reason If set, error messages will also mention this as the reason for the page-not-found.
     * @param array $pageAccessFailureReasons
     * @return ResponseInterface
     * @throws \RuntimeException
     */
    protected function handlePageError($errorHandler, string $header = '', string $reason = '', array $pageAccessFailureReasons = []): ResponseInterface
    {
        $response = null;
        $content = '';
        // Simply boolean; Just shows TYPO3 error page with reason:
        if (is_bool($errorHandler) || strtolower($errorHandler) === 'true' || (string)$errorHandler === '1') {
            $content = GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
                'Page Not Found',
                'The page did not exist or was inaccessible.' . ($reason ? ' Reason: ' . $reason : '')
            );
        } elseif (GeneralUtility::isFirstPartOfStr($errorHandler, 'USER_FUNCTION:')) {
            $funcRef = trim(substr($errorHandler, 14));
            $params = [
                'currentUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                'reasonText' => $reason,
                'pageAccessFailureReasons' => $pageAccessFailureReasons
            ];
            try {
                $content = GeneralUtility::callUserFunction($funcRef, $params, $this);
            } catch (\Exception $e) {
                throw new \RuntimeException('Error: 404 page by USER_FUNCTION "' . $funcRef . '" failed.', 1518472235, $e);
            }
        } elseif (GeneralUtility::isFirstPartOfStr($errorHandler, 'READFILE:')) {
            $readFile = GeneralUtility::getFileAbsFileName(trim(substr($errorHandler, 9)));
            if (@is_file($readFile)) {
                $content = str_replace(
                    [
                        '###CURRENT_URL###',
                        '###REASON###'
                    ],
                    [
                        GeneralUtility::getIndpEnv('REQUEST_URI'),
                        htmlspecialchars($reason)
                    ],
                    file_get_contents($readFile)
                );
            } else {
                throw new \RuntimeException('Configuration Error: 404 page "' . $readFile . '" could not be found.', 1518472245);
            }
        } elseif (GeneralUtility::isFirstPartOfStr($errorHandler, 'REDIRECT:')) {
            $response = new RedirectResponse(substr($errorHandler, 9));
        } elseif ($errorHandler !== '') {
            // Check if URL is relative
            $urlParts = parse_url($errorHandler);
            // parse_url could return an array without the key "host", the empty check works better than strict check
            if (empty($urlParts['host'])) {
                $urlParts['host'] = GeneralUtility::getIndpEnv('HTTP_HOST');
                if ($errorHandler[0] === '/') {
                    $errorHandler = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . $errorHandler;
                } else {
                    $errorHandler = GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR') . $errorHandler;
                }
                $checkBaseTag = false;
            } else {
                $checkBaseTag = true;
            }
            // Check recursion
            if ($errorHandler === GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')) {
                $reason = $reason ?: 'Page cannot be found.';
                $reason .= LF . LF . 'Additionally, ' . $errorHandler . ' was not found while trying to retrieve the error document.';
                throw new \RuntimeException(nl2br(htmlspecialchars($reason)), 1518472252);
            }
            // Prepare headers
            $requestHeaders = [
                'User-agent' => GeneralUtility::getIndpEnv('HTTP_USER_AGENT'),
                'Referer' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')
            ];
            $report = [];
            $res = GeneralUtility::getUrl($errorHandler, 1, $requestHeaders, $report);
            if ((int)$report['error'] !== 0 && (int)$report['error'] !== 200) {
                throw new \RuntimeException('Failed to fetch error page "' . $errorHandler . '", reason: ' . $report['message'], 1518472257);
            }
            if ($res === false) {
                // Last chance -- redirect
                $response = new RedirectResponse($errorHandler);
            } else {
                // Header and content are separated by an empty line
                list($returnedHeaders, $content) = explode(CRLF . CRLF, $res, 2);
                $content .= CRLF;
                // Forward these response headers to the client
                $forwardHeaders = [
                    'Content-Type:'
                ];
                $headerArr = preg_split('/\\r|\\n/', $returnedHeaders, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($headerArr as $headerLine) {
                    foreach ($forwardHeaders as $h) {
                        if (preg_match('/^' . $h . '/', $headerLine)) {
                            $header .= CRLF . $headerLine;
                        }
                    }
                }
                // Put <base> if necessary
                if ($checkBaseTag) {
                    // If content already has <base> tag, we do not need to do anything
                    if (false === stristr($content, '<base ')) {
                        // Generate href for base tag
                        $base = $urlParts['scheme'] . '://';
                        if ($urlParts['user'] != '') {
                            $base .= $urlParts['user'];
                            if ($urlParts['pass'] != '') {
                                $base .= ':' . $urlParts['pass'];
                            }
                            $base .= '@';
                        }
                        $base .= $urlParts['host'];
                        // Add path portion skipping possible file name
                        $base .= preg_replace('/(.*\\/)[^\\/]*/', '${1}', $urlParts['path']);
                        // Put it into content (generate also <head> if necessary)
                        $replacement = LF . '<base href="' . htmlentities($base) . '" />' . LF;
                        if (stristr($content, '<head>')) {
                            $content = preg_replace('/(<head>)/i', '\\1' . $replacement, $content);
                        } else {
                            $content = preg_replace('/(<html[^>]*>)/i', '\\1<head>' . $replacement . '</head>', $content);
                        }
                    }
                }
            }
        } else {
            $content = GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
                'Page Not Found',
                $reason ? 'Reason: ' . $reason : 'Page cannot be found.'
            );
        }

        if (!$response) {
            $response = new HtmlResponse($content);
        }
        return $this->applySanitizedHeadersToResponse($response, $header);
    }

    /**
     * Headers which have been requested, will be added to the response object.
     * If a header is part of the HTTP Response code, the response object will be annotated as well.
     *
     * @param ResponseInterface $response
     * @param string $headers
     * @return ResponseInterface
     */
    protected function applySanitizedHeadersToResponse(ResponseInterface $response, string $headers): ResponseInterface
    {
        if (!empty($headers)) {
            $headerArr = preg_split('/\\r|\\n/', $headers, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($headerArr as $headerLine) {
                if (strpos($headerLine, 'HTTP/') === 0 && strpos($headerLine, ':') === false) {
                    list($protocolVersion, $statusCode, $reasonPhrase) = explode(' ', $headerLine, 3);
                    list(, $protocolVersion) = explode('/', $protocolVersion, 2);
                    $response = $response
                        ->withProtocolVersion((int)$protocolVersion)
                        ->withStatus($statusCode, $reasonPhrase);
                } else {
                    list($headerName, $value) = GeneralUtility::trimExplode(':', $headerLine, 2);
                    $response = $response->withHeader($headerName, $value);
                }
            }
        }
        return $response;
    }

    /**
     * Checks if a site is configured, and an error handler is configured for this specific status code.
     *
     * @param ServerRequestInterface $request
     * @param int $statusCode
     * @return PageErrorHandlerInterface|null
     */
    protected function getErrorHandlerFromSite(ServerRequestInterface $request, int $statusCode): ?PageErrorHandlerInterface
    {
        $site = $request->getAttribute('site');
        if ($site instanceof Site) {
            try {
                return $site->getErrorHandler($statusCode);
            } catch (PageErrorHandlerNotConfiguredException $e) {
                // No error handler found, so fallback back to the generic TYPO3 error handler.
            }
        }
        return null;
    }
}
