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

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Exception\NoAccessibleModuleException;
use TYPO3\CMS\Backend\Middleware\BackendModuleValidator;
use TYPO3\CMS\Backend\Module\ModuleFactory;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BackendModuleValidatorTest extends FunctionalTestCase
{
    protected BackendModuleValidator $subject;
    protected ServerRequestInterface $request;
    protected RequestHandlerInterface $requestHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users_core.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->subject = new BackendModuleValidator(
            $this->get(UriBuilder::class),
            $this->get(ModuleProvider::class),
            $this->get(FlashMessageService::class),
            $this->get(TcaSchemaFactory::class),
        );
        $this->request = new ServerRequest('/some/uri');
        $this->requestHandler = new class () implements RequestHandlerInterface {
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

    #[Test]
    public function processReturnsForbiddenResponseIfModuleInheritanceAccessCheckFails(): void
    {
        $this->setUpBackendUser(2);

        $GLOBALS['TYPO3_REQUEST'] = $request = $this->request->withAttribute(
            'route',
            new Route('/some/route', ['inheritAccessFromModule' => 'web_layout']),
        );

        $response = $this->subject->process($request, $this->requestHandler);

        self::assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function processReturnsOkResponseIfModuleInheritanceAccessCheckIsSuccessful(): void
    {
        $this->setUpBackendUser(3);

        $GLOBALS['TYPO3_REQUEST'] = $request = $this->request->withAttribute(
            'route',
            new Route('/some/route', ['inheritAccessFromModule' => 'web_layout']),
        );

        $response = $this->subject->process($request, $this->requestHandler);

        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function moduleIsAddedToRequest(): void
    {
        $module = $this->get(ModuleFactory::class)->createModule(
            'web_layout',
            [
                'packageName' => 'typo3/cms-testing',
                'path' => '/module/web/layout',
            ]
        );

        $request = $this->request->withAttribute('route', new Route('/some/route', ['module' => $module]));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->process(
            $request,
            $this->requestHandler
        );

        self::assertEquals('web_layout', $response->getHeaderLine('X-Module-identifier'));
    }

    #[Test]
    public function moduleDataIsAddedToRequest(): void
    {
        $module = $this->get(ModuleFactory::class)->createModule(
            'web_layout',
            [
                'packageName' => 'typo3/cms-testing',
                'path' => '/module/web/layout',
                'moduleData' => [
                    'sort' => 'name',
                    'pointer' => 12,
                    'reverse' => false,
                ],
            ]
        );

        $request = $this->request
            ->withQueryParams(['pointer' => 0, 'reverse' => true])
            ->withAttribute('route', new Route('/some/route', ['module' => $module]));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $response = $this->subject->process(
            $request,
            $this->requestHandler
        );

        self::assertEquals('name', $response->getHeaderLine('X-ModuleData-sort'));
        self::assertEquals('0', $response->getHeader('X-ModuleData-pointer')[0]);
        self::assertEquals('1', $response->getHeader('X-ModuleData-reverse')[0]);
    }

    #[Test]
    public function invalidModuleIsHandledWithRedirect(): void
    {
        $module = $this->get(ModuleFactory::class)->createModule(
            'some_module',
            [
                'packageName' => 'typo3/cms-testing',
                'path' => '/some/module',
            ]
        );

        $response = $this->subject->process(
            $this->request->withAttribute('route', new Route('/some/route', ['module' => $module])),
            $this->requestHandler
        );

        self::assertEquals(302, $response->getStatusCode());
        self::assertStringContainsString(
            $this->get(ModuleProvider::class)->getFirstAccessibleModule($GLOBALS['BE_USER'])->getPath(),
            $response->getHeaderLine('location')
        );
    }

    #[Test]
    public function flashMessageIsDispatchedForForcedRedirect(): void
    {
        $module = $this->get(ModuleFactory::class)->createModule(
            'some_module',
            [
                'packageName' => 'typo3/cms-testing',
                'path' => '/some/module',
            ]
        );

        $this->subject->process(
            $this->request->withAttribute('route', new Route('/some/route', ['module' => $module])),
            $this->requestHandler
        );

        $flashMessage = $this->get(FlashMessageService::class)
            ->getMessageQueueByIdentifier(FlashMessageQueue::NOTIFICATION_QUEUE)
            ->getAllMessages()[0] ?? null;

        self::assertInstanceOf(FlashMessage::class, $flashMessage);
        self::assertEquals('No module access', $flashMessage->getTitle());
        self::assertEquals(ContextualFeedbackSeverity::INFO, $flashMessage->getSeverity());
    }

    #[Test]
    public function invalidModuleThrowsException(): void
    {
        $GLOBALS['BE_USER']->user['admin'] = 0;

        // site_configuration requires admin access
        $module = $this->get(ModuleFactory::class)->createModule(
            'site_configuration',
            [
                'packageName' => 'typo3/cms-testing',
                'path' => '/module/site/configuration',
            ]
        );

        $this->expectException(NoAccessibleModuleException::class);
        $this->expectExceptionCode(1702480600);

        $this->subject->process(
            $this->request->withAttribute('route', new Route('/some/route', ['module' => $module])),
            $this->requestHandler
        );
    }

    #[Test]
    public function noPageAccessThrowsException(): void
    {
        $module = $this->get(ModuleFactory::class)->createModule(
            'web_layout',
            [
                'packageName' => 'typo3/cms-testing',
                'path' => '/module/web/layout',
            ]
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

    #[Test]
    public function redirectsToMainForSecFetchDestHeader(): void
    {
        $module = $this->get(ModuleFactory::class)->createModule(
            'web_layout',
            [
                'packageName' => 'typo3/cms-testing',
                'path' => '/module/web/layout',
            ]
        );

        $request = $this->request
            ->withHeader('Sec-Fetch-Dest', 'document')
            ->withAttribute('route', new Route('/some/route', ['_identifier' => 'web_layout', 'module' => $module]));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->process(
            $request,
            $this->requestHandler
        );

        self::assertEquals(302, $response->getStatusCode());
        self::assertMatchesRegularExpression('/^\/typo3\/main.*redirect=web_layout$/', $response->getHeaderLine('location'));
    }
}
