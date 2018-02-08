<?php
namespace TYPO3\CMS\Install\Http;

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
use TYPO3\CMS\Core\Http\AbstractApplication;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;

/**
 * Entry point for the TYPO3 Install Tool
 */
class Application extends AbstractApplication
{
    /**
     * All available request handlers that can handle an install tool request
     * @var array
     */
    protected $availableRequestHandlers = [];

    /**
     * Construct Application
     *
     * @param RequestHandlerInterface $requestHandler
     * @param RequestHandlerInterface $installerRequestHandler
     */
    public function __construct(
        RequestHandlerInterface $requestHandler,
        RequestHandlerInterface $installerRequestHandler
    ) {
        $this->availableRequestHandlers = [
            $requestHandler,
            $installerRequestHandler
        ];
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        foreach ($this->availableRequestHandlers as $handler) {
            if ($handler->canHandleRequest($request)) {
                return $handler->handleRequest($request);
            }
        }

        throw new \TYPO3\CMS\Core\Exception('No suitable request handler found.', 1518448686);
    }
}
