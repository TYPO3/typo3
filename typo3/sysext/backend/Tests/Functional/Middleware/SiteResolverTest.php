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

namespace TYPO3\CMS\Backend\Tests\Functional\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Middleware\SiteResolver;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SiteResolverTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function requestHasNullSiteAttributeIfIdParameterIsNoInteger(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1668696350);
        $incomingUrl = 'http://localhost:8080/typo3/module/file/FilelistList?token=d7d864db2b26c1d0f0537718b16890f336f4af2b&id=9831:/styleguide/';
        $subject = $this->get(SiteResolver::class);
        $incomingRequest = new ServerRequest($incomingUrl, 'GET');
        $incomingRequest = $incomingRequest->withQueryParams(['id' => '9831:/styleguide/']);
        $requestHandler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if ($request->getAttribute('site') instanceof NullSite) {
                    throw new \RuntimeException('testing', 1668696350);
                }
                return new JsonResponse();
            }
        };
        $subject->process($incomingRequest, $requestHandler);
    }
}
