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

namespace TYPO3\CMS\Core\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

interface ResponseFactoryInterface extends \Psr\Http\Message\ResponseFactoryInterface
{
    public function createHtmlResponse(string $html): ResponseInterface;
    public function createJsonResponse(string $json): ResponseInterface;
    public function createRedirectResponse(UriInterface $uri, int $code = 303): ResponseInterface;
}
