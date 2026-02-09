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
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Error\Http\InternalServerErrorException;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerNotConfiguredException;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Middleware\ContentSecurityPolicyHeaders;

/**
 * This controller provides actions for common HTTP error scenarios (404, 403, 500, 503) and supports custom error
 * handling through site-specific error handlers. If no custom error handler is configured, it falls back to
 * rendering a standard TYPO3 error page with appropriate status code and message.
 */
#[Autoconfigure(public: true)]
class ErrorController
{
    public function __construct(
        private readonly ContentSecurityPolicyHeaders $contentSecurityPolicyHeaders,
    ) {}

    /**
     * Used for creating a 500 response ("Internal Server Error"), usually due to some misconfiguration.
     * If a page unavailable handler is configured, a RedirectResponse could be returned as well.
     *
     * @throws InternalServerErrorException
     */
    public function internalErrorAction(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        if ($this->isRequestFromDevIp($request)) {
            throw new InternalServerErrorException($message, 1607585445);
        }
        $errorHandler = $this->getErrorHandlerFromSite($request, 500);
        if ($errorHandler !== null) {
            return $errorHandler->handlePageError($request, $message, $reasons);
        }
        $response = $this->handleError(
            $request,
            500,
            'Internal Server Error',
            'An error occurred while processing your request. Please try again later.',
            $message
        );
        return $this->contentSecurityPolicyHeaders->applyToResponse($request, $response);
    }

    /**
     * Used for creating a 503 response ("Service Unavailable"), to be used for maintenance mode
     * or when the server is overloaded, a RedirectResponse could be returned as well.
     *
     * @throws ServiceUnavailableException
     */
    public function unavailableAction(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        if ($this->isRequestFromDevIp($request)) {
            throw new ServiceUnavailableException($message, 1518472181);
        }
        $errorHandler = $this->getErrorHandlerFromSite($request, 503);
        if ($errorHandler !== null) {
            return $errorHandler->handlePageError($request, $message, $reasons);
        }
        $response = $this->handleError(
            $request,
            503,
            'Service Unavailable',
            'The application is currently down for maintenance. Please check back shortly.',
            $message
        );
        return $this->contentSecurityPolicyHeaders->applyToResponse($request, $response);
    }

    /**
     * Used for creating a 404 response ("Page Not Found"), but if configured, a RedirectResponse could be returned
     * as well.
     *
     * @throws PageNotFoundException
     */
    public function pageNotFoundAction(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        $errorHandler = $this->getErrorHandlerFromSite($request, 404);
        if ($errorHandler !== null) {
            return $errorHandler->handlePageError($request, $message, $reasons);
        }
        try {
            $response = $this->handleError(
                $request,
                404,
                'Page Not Found',
                'The page did not exist or was inaccessible.',
                $message
            );
            return $this->contentSecurityPolicyHeaders->applyToResponse($request, $response);
        } catch (\RuntimeException) {
            throw new PageNotFoundException($message, 1518472189);
        }

    }

    /**
     * Used for creating a 403 response ("Access denied"), but if configured, a RedirectResponse could be returned
     * as well.
     *
     * @throws PageNotFoundException
     */
    public function accessDeniedAction(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        $errorHandler = $this->getErrorHandlerFromSite($request, 403);
        if ($errorHandler !== null) {
            return $errorHandler->handlePageError($request, $message, $reasons);
        }
        try {
            $response = $this->handleError(
                $request,
                403,
                'Access Denied',
                'You do not have the necessary permissions to access this resource.',
                $message
            );
            return $this->contentSecurityPolicyHeaders->applyToResponse($request, $response);
        } catch (\RuntimeException) {
            throw new PageNotFoundException($message, 1518472195);
        }
    }

    /**
     * Used for creating an error with a custom status code, but if configured, a RedirectResponse could be
     * returned as well.
     *
     * @param array<string, mixed> $reasons An array of reasons for evaluation in a possible resolved the error handler
     *
     * @throws PageNotFoundException
     */
    public function customErrorAction(
        ServerRequestInterface $request,
        int $statusCode,
        string $title,
        string $message,
        string $technicalReason = '',
        array $reasons = [],
        int $errorCode = 0
    ): ResponseInterface {
        $errorHandler = $this->getErrorHandlerFromSite($request, $statusCode);
        if ($errorHandler !== null) {
            return $errorHandler->handlePageError($request, $message, $reasons);
        }
        try {
            return $this->handleError($request, $statusCode, $title, $message, $technicalReason, $errorCode);
        } catch (\RuntimeException) {
            throw new PageNotFoundException($message, 1770466857);
        }
    }

    /**
     * Checks whether the devIPMask matches the current visitor's IP address.
     *
     * @return bool False if the server error handler should be used.
     */
    protected function isRequestFromDevIp(ServerRequestInterface $request): bool
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        return GeneralUtility::cmpIP($normalizedParams->getRemoteAddress(), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']);
    }

    /**
     * Checks if a site is configured, and an error handler is configured for this specific status code.
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
     * Handles the error by creating a response object. Acts as a fallback when no error handler is configured.
     */
    protected function handleError(
        ServerRequestInterface $request,
        int $statusCode,
        string $title,
        string $message,
        string $technicalReason = '',
        int $errorCode = 0
    ): ResponseInterface {
        if (str_contains($request->getHeaderLine('Accept'), 'application/json')) {
            return new JsonResponse(['reason' => $technicalReason], $statusCode);
        }
        $content = GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
            $title,
            $message . ($technicalReason ? ' Reason: ' . $technicalReason : ''),
            $errorCode,
            $statusCode
        );
        return new HtmlResponse($content, $statusCode);
    }
}
