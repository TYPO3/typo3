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

namespace MyVendor\MySitePackage\Error\PageErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * An error handler that redirects to a configured page, where the login
 * process is handled. Passes a configurable URL parameter (`return_url` or
 * `redirect_url`) to the target page.
 */
final class RedirectLoginErrorHandler implements PageErrorHandlerInterface
{
    private const PAGE_ID_LOGIN_FORM = 656;

    private int $loginRedirectPid;
    private string $loginRedirectParameter;
    private Context $context;
    private LinkService $linkService;
    private ErrorPageController $errorPageController;
    private int $statusCode;

    public function __construct(int $statusCode)
    {
        $this->statusCode = $statusCode;
        $configuration = [
            // TODO: Replace with $siteSettings[...] or something else
            'loginRedirectTarget' => 't3://page?uid=' . self::PAGE_ID_LOGIN_FORM,
            'loginRedirectParameter' => 'return_url',
        ];

        $this->context = GeneralUtility::makeInstance(Context::class);
        $this->linkService = GeneralUtility::makeInstance(LinkService::class);
        $this->errorPageController = GeneralUtility::makeInstance(ErrorPageController::class);

        $urlParams = $this->linkService->resolve($configuration['loginRedirectTarget']);
        $this->loginRedirectPid = (int)($urlParams['pageuid'] ?? 0);
        $this->loginRedirectParameter = $configuration['loginRedirectParameter'];
    }

    public function handlePageError(
        ServerRequestInterface $request,
        string $message,
        array $reasons = []
    ): ResponseInterface {
        $this->checkHandlerConfiguration();

        if ($this->shouldHandleRequest($reasons)) {
            return $this->handleLoginRedirect($request);
        }

        // Show general error message with a 403 HTTP status code
        return $this->getGenericAccessDeniedResponse($message);
    }

    private function getGenericAccessDeniedResponse(string $reason): ResponseInterface
    {
        $reason = $reason ? ' Reason: ' . $reason : '';
        $content = $this->errorPageController->errorAction(
            'Page Not Found',
            sprintf('The page did not exist or was inaccessible.%s', $reason),
            0,
            $this->statusCode,
        );
        return new HtmlResponse($content, $this->statusCode);
    }

    private function handleLoginRedirect(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->isLoggedIn()) {
            return $this->getGenericAccessDeniedResponse(
                'The requested page was not accessible with the provided credentials'
            );
        }

        /** @var Site $site */
        $site = $request->getAttribute('site');
        $language = $request->getAttribute('language');

        $loginUrl = $site->getRouter()->generateUri(
            $this->loginRedirectPid,
            [
                '_language' => $language,
                $this->loginRedirectParameter => (string)$request->getUri(),
            ]
        );

        return new RedirectResponse($loginUrl);
    }

    private function shouldHandleRequest(array $reasons): bool
    {
        if (!isset($reasons['code'])) {
            return false;
        }

        $accessDeniedReasons = [
            PageAccessFailureReasons::ACCESS_DENIED_PAGE_NOT_RESOLVED,
            PageAccessFailureReasons::ACCESS_DENIED_SUBSECTION_NOT_RESOLVED,
        ];
        $isAccessDenied = in_array($reasons['code'], $accessDeniedReasons, true);

        return $isAccessDenied || $this->isSimulatedBackendGroup();
    }

    private function isLoggedIn(): bool
    {
        if ($this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
            return true;
        }
        return $this->isSimulatedBackendGroup();
    }

    protected function isSimulatedBackendGroup(): bool
    {
        if (!$this->context->getPropertyFromAspect('backend.user', 'isLoggedIn')) {
            return false;
        }
        // look for special "any group"
        $groups = $this->context->getPropertyFromAspect('frontend.user', 'groupIds');
        return $groups[1] === -2;
    }

    private function checkHandlerConfiguration(): void
    {
        if ($this->loginRedirectPid === 0) {
            throw new \RuntimeException('No loginRedirectTarget configured for LoginRedirect errorhandler', 1700813537);
        }

        if ($this->statusCode !== 403) {
            throw new \RuntimeException(sprintf('Invalid HTTP status code %d for LoginRedirect errorhandler', $this->statusCode), 1700813545);
        }
    }
}
