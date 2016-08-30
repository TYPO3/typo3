<?php
namespace TYPO3\CMS\Frontend\Http;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\Dispatcher;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\TimeTracker\NullTimeTracker;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Lightweight alternative to the regular RequestHandler used when $_GET[eID] is set.
 * In the future, logic from the EidUtility will be moved to this class.
 */
class EidRequestHandler implements RequestHandlerInterface
{
    /**
     * Instance of the current TYPO3 bootstrap
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * Constructor handing over the bootstrap and the original request
     *
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * Handles a frontend request based on the _GP "eID" variable.
     *
     * @param ServerRequestInterface $request
     * @return NULL|\Psr\Http\Message\ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        // Timetracking started
        $configuredCookieName = trim($GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName']);
        if (empty($configuredCookieName)) {
            $configuredCookieName = 'be_typo_user';
        }
        if ($request->getCookieParams()[$configuredCookieName]) {
            $GLOBALS['TT'] = new TimeTracker();
        } else {
            $GLOBALS['TT'] = new NullTimeTracker();
        }

        $GLOBALS['TT']->start();

        // Hook to preprocess the current request
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'] as $hookFunction) {
                $hookParameters = [];
                GeneralUtility::callUserFunction($hookFunction, $hookParameters, $hookParameters);
            }
            unset($hookFunction);
            unset($hookParameters);
        }

        // Remove any output produced until now
        $this->bootstrap->endOutputBufferingAndCleanPreviousOutput();
        return $this->dispatch($request);
    }

    /**
     * This request handler can handle any frontend request.
     *
     * @param ServerRequestInterface $request The request to process
     * @return bool If the request is not an eID request, TRUE otherwise FALSE
     */
    public function canHandleRequest(ServerRequestInterface $request)
    {
        return !empty($request->getQueryParams()['eID']) || !empty($request->getParsedBody()['eID']);
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority()
    {
        return 80;
    }

    /**
     * Dispatches the request to the corresponding eID class or eID script
     *
     * @param ServerRequestInterface $request
     * @return NULL|\Psr\Http\Message\ResponseInterface
     * @throws Exception
     */
    protected function dispatch($request)
    {
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);

        $eID = isset($request->getParsedBody()['eID'])
            ? $request->getParsedBody()['eID']
            : (isset($request->getQueryParams()['eID']) ? $request->getQueryParams()['eID'] : '');

        if (empty($eID) || !isset($GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][$eID])) {
            return $response->withStatus(404, 'eID not registered');
        }

        $configuration = $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][$eID];

        // Simple check to make sure that it's not an absolute file (to use the fallback)
        if (strpos($configuration, '::') !== false || is_callable($configuration)) {
            /** @var Dispatcher $dispatcher */
            $dispatcher = GeneralUtility::makeInstance(Dispatcher::class);
            $request = $request->withAttribute('target', $configuration);
            return $dispatcher->dispatch($request, $response);
        }

        $scriptPath = GeneralUtility::getFileAbsFileName($configuration);
        if ($scriptPath === '') {
            throw new Exception('Registered eID has invalid script path.', 1416391467);
        }
        include $scriptPath;
        return null;
    }
}
