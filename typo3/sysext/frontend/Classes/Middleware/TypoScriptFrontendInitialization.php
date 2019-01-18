<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Middleware;

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

use Doctrine\DBAL\Exception\ConnectionException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Creates an instance of TypoScriptFrontendController and makes this globally available
 * via $GLOBALS['TSFE'].
 *
 * @internal this middleware might get removed in TYPO3 v10.0.
 */
class TypoScriptFrontendInitialization implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Creates an instance of TSFE and sets it as a global variable,
     * also pings the database in order ensure a valid database connection.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            null,
            $request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0,
            $request->getParsedBody()['type'] ?? $request->getQueryParams()['type'] ?? 0,
            null,
            $request->getParsedBody()['cHash'] ?? $request->getQueryParams()['cHash'] ?? '',
            null,
            $request->getParsedBody()['MP'] ?? $request->getQueryParams()['MP'] ?? ''
        );
        if ($request->getParsedBody()['no_cache'] ?? $request->getQueryParams()['no_cache'] ?? false) {
            $GLOBALS['TSFE']->set_no_cache('&no_cache=1 has been supplied, so caching is disabled! URL: "' . (string)$request->getUri() . '"');
        }

        // Set up the database connection and see if the connection can be established
        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
            $connection->connect();
        } catch (ConnectionException $exception) {
            // Cannot connect to current database
            $message = 'Cannot connect to the configured database "' . $connection->getDatabase() . '"';
            $this->logger->emergency($message, ['exception' => $exception]);
            try {
                return GeneralUtility::makeInstance(ErrorController::class)->unavailableAction($request, $message);
            } catch (ServiceUnavailableException $e) {
                throw new ServiceUnavailableException($message, 1526013723);
            }
        }
        return $handler->handle($request);
    }
}
