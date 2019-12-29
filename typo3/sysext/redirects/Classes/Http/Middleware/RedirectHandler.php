<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Redirects\Http\Middleware;

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
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Service\RedirectService;

/**
 * Hooks into the frontend request, and checks if a redirect should apply,
 * If so, a redirect response is triggered.
 *
 * @internal
 */
class RedirectHandler implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * First hook within the Frontend Request handling
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $redirectService = GeneralUtility::makeInstance(RedirectService::class);
        $port = $request->getUri()->getPort();
        $matchedRedirect = $redirectService->matchRedirect(
            $request->getUri()->getHost() . ($port ? ':' . $port : ''),
            $request->getUri()->getPath(),
            $request->getUri()->getQuery() ?? ''
        );

        // If the matched redirect is found, resolve it, and check further
        if (is_array($matchedRedirect)) {
            $url = $redirectService->getTargetUrl($matchedRedirect, $request->getQueryParams(), $request->getUri(), $request->getAttribute('site', null));
            if ($url instanceof UriInterface) {
                $this->logger->debug('Redirecting', ['record' => $matchedRedirect, 'uri' => $url]);
                $response = $this->buildRedirectResponse($url, $matchedRedirect);
                $this->incrementHitCount($matchedRedirect);

                return $response;
            }
        }

        return $handler->handle($request);
    }

    /**
     * Creates a PSR-7 compatible Response object
     *
     * @param UriInterface $uri
     * @param array $redirectRecord
     * @return ResponseInterface
     */
    protected function buildRedirectResponse(UriInterface $uri, array $redirectRecord): ResponseInterface
    {
        return new RedirectResponse(
            $uri,
            (int)$redirectRecord['target_statuscode'],
            ['X-Redirect-By' => 'TYPO3 Redirect ' . $redirectRecord['uid']]
        );
    }

    /**
     * Updates the sys_record's hit counter by one
     *
     * @param array $redirectRecord
     */
    protected function incrementHitCount(array $redirectRecord)
    {
        // Track the hit if not disabled
        if (!GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('redirects.hitCount') || $redirectRecord['disable_hitcount']) {
            return;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_redirect');
        $queryBuilder
            ->update('sys_redirect')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($redirectRecord['uid'], \PDO::PARAM_INT))
            )
            ->set('hitcount', $queryBuilder->quoteIdentifier('hitcount') . '+1', false)
            ->set('lasthiton', $GLOBALS['EXEC_TIME'])
            ->execute();
    }
}
