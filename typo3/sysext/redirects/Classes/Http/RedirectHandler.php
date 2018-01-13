<?php
declare(strict_types=1);
namespace TYPO3\CMS\Redirects\Http;

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
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Redirects\Service\RedirectService;

/**
 * Hooks into the frontend request, and checks if a redirect should apply,
 * If so, a redirect response is triggered.
 */
class RedirectHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * First hook within the Frontend Request handling
     */
    public function handle()
    {
        $redirectService = GeneralUtility::makeInstance(RedirectService::class);
        //@todo The request object should be handed in by the hook in the future
        $currentRequest = ServerRequestFactory::fromGlobals();
        $port = $currentRequest->getUri()->getPort();
        $matchedRedirect = $redirectService->matchRedirect(
            $currentRequest->getUri()->getHost() . ($port ? ':' . $port : ''),
            $currentRequest->getUri()->getPath()
        );

        // If the matched redirect is found, resolve it, and check further
        if (!is_array($matchedRedirect)) {
            return;
        }

        $url = $redirectService->getTargetUrl($matchedRedirect, $currentRequest->getQueryParams());
        if ($url instanceof UriInterface) {
            $this->logger->debug('Redirecting', ['record' => $matchedRedirect, 'uri' => $url]);
            $response = $this->buildRedirectResponse($url, $matchedRedirect);
            $this->incrementHitCount($matchedRedirect);
            HttpUtility::sendResponse($response);
        }
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
        return new RedirectResponse($uri, (int)$redirectRecord['target_statuscode'], ['X-Redirect-By' => 'TYPO3']);
    }

    /**
     * Updates the sys_record's hit counter by one
     *
     * @param array $redirectRecord
     */
    protected function incrementHitCount(array $redirectRecord)
    {
        // Track the hit if not disabled
        if ($redirectRecord['disable_hitcount']) {
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
