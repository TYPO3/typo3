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

namespace TYPO3\CMS\Frontend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Error\Http\InternalServerErrorException;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerNotConfiguredException;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handles error requests,
 * returns a response object.
 */
class ErrorController
{
    /**
     * Used for creating a 500 response ("Internal Server Error"), usually due some misconfiguration
     * but if configured, a RedirectResponse could be returned as well.
     *
     * @param ServerRequestInterface $request
     * @param string $message
     * @param array $reasons
     * @return ResponseInterface
     * @throws InternalServerErrorException
     */
    public function internalErrorAction(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        if (!$this->isPageUnavailableHandlerConfigured()) {
            throw new InternalServerErrorException($message, 1607585445);
        }
        $errorHandler = $this->getErrorHandlerFromSite($request, 500);
        if ($errorHandler instanceof PageErrorHandlerInterface) {
            return $errorHandler->handlePageError($request, $message, $reasons);
        }
        return $this->handleDefaultError($request, 500, $message ?: 'Internal Server Error');
    }

    /**
     * Used for creating a 503 response ("Service Unavailable"), to be used for maintenance mode
     * or when the server is overloaded, a RedirectResponse could be returned as well.
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
        $errorHandler = $this->getErrorHandlerFromSite($request, 503);
        if ($errorHandler instanceof PageErrorHandlerInterface) {
            return $errorHandler->handlePageError($request, $message, $reasons);
        }
        return $this->handleDefaultError($request, 503, $message ?: 'Service Unavailable');
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
            return $errorHandler->handlePageError($request, $message, $reasons);
        }
        try {
            return $this->handleDefaultError($request, 404, $message);
        } catch (\RuntimeException $e) {
            throw new PageNotFoundException($message, 1518472189);
        }
    }

    /**
     * Used for creating a 403 response ("Access denied"),
     * but if configured, a RedirectResponse could be returned as well.
     *
     * @param ServerRequestInterface $request
     * @param string $message
     * @param array $reasons
     * @return ResponseInterface
     * @throws PageNotFoundException
     */
    public function accessDeniedAction(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        $errorHandler = $this->getErrorHandlerFromSite($request, 403);
        if ($errorHandler instanceof PageErrorHandlerInterface) {
            return $errorHandler->handlePageError($request, $message, $reasons);
        }
        try {
            return $this->handleDefaultError($request, 403, $message);
        } catch (\RuntimeException $e) {
            throw new PageNotFoundException($message, 1518472195);
        }
    }

    /**
     * Checks whether the devIPMask matches the current visitor's IP address.
     * Note: the name of this method is a misnomer (legacy code),
     *
     * @return bool True if the server error handler should be used.
     */
    protected function isPageUnavailableHandlerConfigured(): bool
    {
        return !GeneralUtility::cmpIP(GeneralUtility::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']);
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

    /**
     * Ensures that a response object is created as a "fallback" when no error handler is configured.
     *
     * @param ServerRequestInterface $request
     * @param int $statusCode
     * @param string $reason
     * @return ResponseInterface
     */
    protected function handleDefaultError(ServerRequestInterface $request, int $statusCode, string $reason = ''): ResponseInterface
    {
        if (str_contains($request->getHeaderLine('Accept'), 'application/json')) {
            return new JsonResponse(['reason' => $reason], $statusCode);
        }
        $content = GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
            'Page Not Found',
            'The page did not exist or was inaccessible.' . ($reason ? ' Reason: ' . $reason : ''),
            AbstractMessage::ERROR,
            0,
            $statusCode
        );
        return new HtmlResponse($content, $statusCode);
    }
}
