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
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * This middleware authenticates a Frontend User (fe_users).
 */
class FrontendUserAuthenticator implements MiddlewareInterface
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Creates a frontend user authentication object, tries to authenticate a user and stores
     * it in the current request as attribute.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $frontendUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);

        // List of page IDs where to look for frontend user records
        $pid = $request->getParsedBody()['pid'] ?? $request->getQueryParams()['pid'] ?? 0;
        if ($pid) {
            $frontendUser->checkPid_value = implode(',', GeneralUtility::intExplode(',', $pid));
        }

        // Authenticate now
        $frontendUser->start();
        $frontendUser->unpack_uc();
        // no matter if we have an active user we try to fetch matching groups which can
        // be set without an user (simulation for instance!)
        $frontendUser->fetchGroupData();

        // Register the frontend user as aspect and within the request
        $userAspect = $frontendUser->createUserAspect();
        $this->context->setAspect('frontend.user', $userAspect);
        $request = $request->withAttribute('frontend.user', $frontendUser);

        $response = $handler->handle($request);

        // Store session data for fe_users if it still exists
        if ($frontendUser instanceof FrontendUserAuthentication) {
            $frontendUser->storeSessionData();
            $response = $frontendUser->appendCookieToResponse($response);
            // Collect garbage in Frontend requests, which aren't fully cacheable (e.g. with cookies)
            if ($response->hasHeader('Set-Cookie')) {
                $this->sessionGarbageCollection();
            }
        }

        return $response;
    }

    /**
     * Garbage collection for fe_sessions (with a probability)
     */
    protected function sessionGarbageCollection(): void
    {
        UserSessionManager::create('FE')->collectGarbage();
    }
}
