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
 * For now, GeneralUtility::_GP() is used in favor of $request->getQueryParams() due to
 * hooks who could have $_GET/$_POST modified before.
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
            GeneralUtility::_GP('id'),
            GeneralUtility::_GP('type'),
            null,
            GeneralUtility::_GP('cHash'),
            null,
            GeneralUtility::_GP('MP')
        );
        if (GeneralUtility::_GP('no_cache')) {
            $GLOBALS['TSFE']->set_no_cache('&no_cache=1 has been supplied, so caching is disabled! URL: "' . (string)$request->getUri() . '"');
        }

        // Set up the database connection and see if the connection can be established
        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
            $connection->connect();
        } catch (ConnectionException | \RuntimeException $exception) {
            $message = 'Cannot connect to the configured database';
            $this->logger->emergency($message, ['exception' => $exception]);
            try {
                return GeneralUtility::makeInstance(ErrorController::class)->unavailableAction($request, $message);
            } catch (ServiceUnavailableException $e) {
                throw new ServiceUnavailableException($message, 1526013723);
            }
        }
        // Call post processing function for DB connection:
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB'])) {
            trigger_error('The "connectToDB" hook will be removed in TYPO3 v10.0 in favor of PSR-15. Use a middleware instead.', E_USER_DEPRECATED);
            $_params = ['pObj' => &$GLOBALS['TSFE']];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $GLOBALS['TSFE']);
            }
        }

        return $handler->handle($request);
    }
}
