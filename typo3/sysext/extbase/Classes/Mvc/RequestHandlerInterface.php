<?php
namespace TYPO3\CMS\Extbase\Mvc;

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
 *
 * @api
 */
interface RequestHandlerInterface
{
    /**
     * Handles a raw request and returns the respsonse.
     *
     * @return \TYPO3\CMS\Extbase\Mvc\ResponseInterface
     * @api
     */
    public function handleRequest();

    /**
     * Checks if the request handler can handle the current request.
     *
     * @return bool TRUE if it can handle the request, otherwise FALSE
     * @api
     */
    public function canHandleRequest();

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
