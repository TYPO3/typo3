<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Install\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;

/**
 * Fallback request handler for all requests inside the TYPO3 Install Tool.
 * Returns a 404 status code, in case none of the previously executed middlewares handled the request.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class NotFoundRequestHandler implements RequestHandlerInterface
{
    /**
     * Handles an Install Tool request when previously executed middlewares didn't handle thr request.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse('', 404);
    }
}
