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
use TYPO3\CMS\Backend\Middleware\BackendModuleValidator;
use TYPO3\CMS\Backend\Module\ModuleFactory;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendModuleValidatorTest extends FunctionalTestCase
{
    protected BackendModuleValidator $subject;
    protected ServerRequestInterface $request;
    protected RequestHandlerInterface $requestHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $this->subject = new BackendModuleValidator(
            $this->getContainer()->get(UriBuilder::class),
            $this->getContainer()->get(ModuleProvider::class),
        );
        $this->request = new ServerRequest('/some/uri');
        $this->requestHandler = new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                // In case the module is valid, it is added to the request, together with the
                // module data. To test this, we add some properties as header to the response.
                return (new Response())
                    ->withHeader('X-Module-identifier', (string)($request->getAttribute('module')?->getIdentifier() ?? ''))
                    ->withHeader('X-ModuleData-sort', (string)($request->getAttribute('moduleData')?->get('sort') ?? ''))
                    ->withHeader('X-ModuleData-pointer', (string)($request->getAttribute('moduleData')?->get('pointer') ?? '12'))
                    ->withHeader('X-ModuleData-reverse', (string)($request->getAttribute('moduleData')?->get('reverse') ?? '0'));
            }
        };
    }

    /**
     * @test
     */
    public function moduleIsAddedToRequest(): void
    {
        $module = $this->getContainer()->get(ModuleFactory::class)->createModule(
            'web_layout',
            ['path' => '/module/web/layout']
        );

        $response = $this->subject->process(
            $this->request->withAttribute('route', new Route('/some/route', ['module' => $module])),
            $this->requestHandler
        );

        self::assertEquals('web_layout', $response->getHeaderLine('X-Module-identifier'));
    }

    /**
     * @test
     */
    public function moduleDataIsAddedToRequest(): void
    {
        $module = $this->getContainer()->get(ModuleFactory::class)->createModule(
            'web_layout',
            [
                'path' => '/module/web/layout',
                'moduleData' => [
                    'sort' => 'name',
                    'pointer' => 12,
                    'reverse' => false,
                ],
            ]
        );

        $response = $this->subject->process(
            $this->request
                ->withQueryParams(['pointer' => 0, 'reverse' => true])
                ->withAttribute('route', new Route('/some/route', ['module' => $module])),
            $this->requestHandler
        );

        self::assertEquals('name', $response->getHeaderLine('X-ModuleData-sort'));
        self::assertEquals('0', $response->getHeader('X-ModuleData-pointer')[0]);
        self::assertEquals('1', $response->getHeader('X-ModuleData-reverse')[0]);
    }

    /**
     * @test
     */
    public function invalidModuleThrowsException(): void
    {
        $module = $this->getContainer()->get(ModuleFactory::class)->createModule(
            'some_module',
            ['path' => '/some/module']
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1642450334);

        $this->subject->process(
            $this->request->withAttribute('route', new Route('/some/route', ['module' => $module])),
            $this->requestHandler
        );
    }

    /**
     * @test
     */
    public function insufficientAccessPermissionsThrowsException(): void
    {
        $GLOBALS['BE_USER']->user['admin'] = 0;

        // site_configuration requires admin access
        $module = $this->getContainer()->get(ModuleFactory::class)->createModule(
            'site_configuration',
            ['path' => '/module/site/configuration']
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1642450334);

        $this->subject->process(
            $this->request->withAttribute('route', new Route('/some/route', ['module' => $module])),
            $this->requestHandler
        );
    }

    /**
     * @test
     */
    public function noPageAccessThrowsException(): void
    {
        $module = $this->getContainer()->get(ModuleFactory::class)->createModule(
            'web_layout',
            ['path' => '/module/web/layout']
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1289917924);

        $this->subject->process(
            $this->request
                ->withQueryParams(['id' => 123])
                ->withAttribute('route', new Route('/some/route', ['module' => $module])),
            $this->requestHandler
        );
    }

    /**
     * @test
     */
    public function redirectsToMainForSecFetchDestHeader(): void
    {
        $module = $this->getContainer()->get(ModuleFactory::class)->createModule(
            'web_layout',
            ['path' => '/module/web/layout']
        );

        $response = $this->subject->process(
            $this->request
                ->withHeader('Sec-Fetch-Dest', 'document')
                ->withAttribute('route', new Route('/some/route', ['_identifier' => 'web_layout', 'module' => $module])),
            $this->requestHandler
        );

        self::assertEquals(302, $response->getStatusCode());
        self::assertMatchesRegularExpression('/^\/typo3\/main.*redirect=web_layout$/', $response->getHeaderLine('location'));
    }
}
