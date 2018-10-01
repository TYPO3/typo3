<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Http;

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

/**
 * The interface for a request handler
 * see RequestHandler in EXT:backend/Classes/Http/ and EXT:frontend/Classes/Http
 *
 * @internal although TYPO3 Core still uses this in TYPO3 v9, this will be removed with PSR-15 RequestHandlerInterface
 */
interface RequestHandlerInterface
{
    /**
     * Handles a raw request
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request);

    /**
     * Checks if the request handler can handle the given request.
     *
     * @param ServerRequestInterface $request
     * @return bool TRUE if it can handle the request, otherwise FALSE
     */
    public function canHandleRequest(ServerRequestInterface $request);

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request. An integer > 0 means "I want to handle this request" where
     * "100" is default. "0" means "I am a fallback solution".
     *
     * @return int The priority of the request handler
     */
    public function getPriority();
}
