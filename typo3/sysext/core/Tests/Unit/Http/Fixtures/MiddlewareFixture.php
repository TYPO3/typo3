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

namespace TYPO3\CMS\Core\Tests\Unit\Http\Fixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * MiddlewareFixture
 */
class MiddlewareFixture implements MiddlewareInterface
{
    /**
     * @var string
     */
    public static $id = '0';

    /**
     * @var bool
     */
    public static $hasBeenInstantiated = false;

    public function __construct()
    {
        static::$hasBeenInstantiated = true;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAddedHeader('X-SEQ-PRE-REQ-HANDLER', static::$id);
        $response = $handler->handle($request);

        return $response->withAddedHeader('X-SEQ-POST-REQ-HANDLER', static::$id);
    }
}
