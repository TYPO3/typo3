<?php
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

/**
 * The interface for a request handler
 * see RequestHandler in EXT:backend/Classes/Http/ and EXT:frontend/Classes/Http
 *
 * @api
 */
interface RequestHandlerInterface
{
    /**
     * Handles a raw request
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return NULL|\Psr\Http\Message\ResponseInterface
     * @api
     */
    public function handleRequest(\Psr\Http\Message\ServerRequestInterface $request);

    /**
     * Checks if the request handler can handle the given request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return bool TRUE if it can handle the request, otherwise FALSE
     * @api
     */
    public function canHandleRequest(\Psr\Http\Message\ServerRequestInterface $request);

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request. An integer > 0 means "I want to handle this request" where
     * "100" is default. "0" means "I am a fallback solution".
     *
     * @return int The priority of the request handler
     * @api
     */
    public function getPriority();
}
