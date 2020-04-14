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

namespace TYPO3\CMS\Backend\Tests\Unit\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Middleware\SiteResolver;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SiteResolverTest extends UnitTestCase
{

    /**
     * @test
     */
    public function RequestIsNotModifiedIfPageIdParameterIsNoInteger()
    {
        $incomingUrl = 'http://localhost:8080/typo3/index.php?route=/file/FilelistList/&token=d7d864db2b26c1d0f0537718b16890f336f4af2b&id=9831:/styleguide/';

        $siteMatcherProphecy = $this->prophesize(SiteMatcher::class);
        $subject = new SiteResolver($siteMatcherProphecy->reveal());

        $incomingRequest = new ServerRequest($incomingUrl, 'GET');
        $incomingRequest = $incomingRequest->withQueryParams(['id' => '9831:/styleguide/']);
        $requestHandler = new class() implements RequestHandlerInterface {
            public $incomingRequest;
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new JsonResponse([], $request === $this->incomingRequest ? 200 : 500);
            }
            public function setIncomingRequest(ServerRequestInterface $incomingRequest)
            {
                $this->incomingRequest = $incomingRequest;
            }
        };
        $requestHandler->setIncomingRequest($incomingRequest);
        $response = $subject->process($incomingRequest, $requestHandler);
        self::assertEquals(200, $response->getStatusCode());
    }
}
