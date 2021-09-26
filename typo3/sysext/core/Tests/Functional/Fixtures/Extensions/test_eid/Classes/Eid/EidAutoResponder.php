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

namespace TYPO3\TestEid\Eid;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * Eid AutoResponder
 */
class EidAutoResponder
{
    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        // simply return some data which may be checked.
        return new JsonResponse(
            [
                'eid_responder' => true,
                'uri' => (string)$request->getUri(),
                'method' => $request->getMethod(),
                'queryParams' => $request->getQueryParams(),
            ],
            200,
            [
                'content-type'  => 'application/json',
                'eid_responder' => 'responded',
            ],
            JsonResponse::DEFAULT_JSON_FLAGS
        );
    }
}
