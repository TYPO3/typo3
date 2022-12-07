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

namespace TYPO3\CMS\Install\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Used from backend `/typo3` context to check webserver response in general (independent of install tool).
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class ServerResponseCheckController
{
    public static function hmac(string $value): string
    {
        return GeneralUtility::hmac($value, ServerResponseCheckController::class);
    }

    public function checkHostAction(ServerRequestInterface $request): ResponseInterface
    {
        $time = $request->getQueryParams()['src-time'] ?? null;
        $hash = $request->getQueryParams()['src-hash'] ?? null;

        if (empty($time) || !is_string($time) || empty($hash) || !is_string($hash)) {
            return new JsonResponse(['error' => 'Query params src-time` and src-hash` are required.'], 400);
        }
        if (!hash_equals(self::hmac($time), $hash)) {
            return new JsonResponse(['error' => 'Invalid time or hash provided.'], 400);
        }
        if ((int)$time + 60 < time()) {
            return new JsonResponse(['error' => 'Request expired.'], 400);
        }

        return new JsonResponse([
            'server.HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
            'server.SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? null,
            'server.SERVER_PORT' => $_SERVER['SERVER_PORT'] ?? null,
        ]);
    }
}
