<?php

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

namespace TYPO3\CMS\Extbase\Mvc\Web;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Dispatcher;
use TYPO3\CMS\Extbase\Mvc\Exception\InfiniteLoopException;
use TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

/**
 * A request handler which can handle web requests.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class FrontendRequestHandler implements RequestHandlerInterface
{
    protected Dispatcher $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Handles the web request. The response will automatically be sent to the client.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws InfiniteLoopException
     */
    public function handleRequest(RequestInterface $request)
    {
        return $this->dispatcher->dispatch($request);
    }

    /**
     * This request handler can handle any web request.
     *
     * @param RequestInterface $request
     * @return bool If the request is a web request, TRUE otherwise FALSE
     */
    public function canHandleRequest(RequestInterface $request)
    {
        return true;
    }

    public function getPriority(): int
    {
        return 100;
    }
}
