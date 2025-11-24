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
use TYPO3\CMS\Backend\Context\PageContext;
use TYPO3\CMS\Backend\Middleware\PageContextInitialization;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageContextInitializationTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];
    private BackendUserAuthentication $backendUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Authentication/Fixtures/be_users.csv');
        $this->backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages_and_content.csv');
    }

    #[Test]
    public function determinesPageIdFromQueryParameter(): void
    {
        $request = $this->createRequest()->withQueryParams(['id' => 1]);
        $pageContext = $this->processMiddleware($request);
        self::assertSame(1, $pageContext->pageId);
    }

    #[Test]
    public function determinesPageIdFromBodyParameter(): void
    {
        $request = $this->createRequest()->withParsedBody(['id' => 2]);
        $pageContext = $this->processMiddleware($request);
        self::assertSame(2, $pageContext->pageId);
    }

    #[Test]
    public function bodyParameterTakesPrecedenceOverQueryParameter(): void
    {
        $request = $this->createRequest()->withQueryParams(['id' => 1])->withParsedBody(['id' => 2]);
        $pageContext = $this->processMiddleware($request);
        self::assertSame(2, $pageContext->pageId);
    }

    #[Test]
    public function determinesPageIdFromEditStatementForEditAction(): void
    {
        // edit[pages][1]=edit means editing page 1
        $request = $this->createRequest()->withQueryParams(['edit' => ['pages' => [1 => 'edit']]]);
        $pageContext = $this->processMiddleware($request);
        self::assertSame(1, $pageContext->pageId);
    }

    #[Test]
    public function determinesPageIdFromEditStatementForNewAction(): void
    {
        // edit[pages][1]=new means creating new page under page 1
        $request = $this->createRequest()->withQueryParams(['edit' => ['pages' => [1 => 'new']]]);
        $pageContext = $this->processMiddleware($request);
        self::assertSame(1, $pageContext->pageId);
    }

    #[Test]
    public function determinesPageIdFromEditStatementForContentEditAction(): void
    {
        // edit[tt_content][1]=edit means editing content record 1
        // Should resolve to the page where the content is located
        $request = $this->createRequest()->withQueryParams(['edit' => ['tt_content' => [1 => 'edit']]]);
        $pageContext = $this->processMiddleware($request);
        // Content record 1 is on page 1 (from fixtures)
        self::assertSame(1, $pageContext->pageId);
    }

    #[Test]
    public function determinesPageIdFromEditStatementForContentNewAction(): void
    {
        // edit[tt_content][2]=new means creating content on page 2
        $request = $this->createRequest()->withQueryParams(['edit' => ['tt_content' => [2 => 'new']]]);
        $pageContext = $this->processMiddleware($request);
        self::assertSame(2, $pageContext->pageId);
    }

    #[Test]
    public function determinesPageIdFromCmdStatementForDeleteAction(): void
    {
        // cmd[tt_content][1][delete]=1 means deleting content record 1
        $request = $this->createRequest()->withQueryParams(['cmd' => ['tt_content' => [1 => ['delete' => 1]]]]);
        $pageContext = $this->processMiddleware($request);
        // Content record 1 is on page 1
        self::assertSame(1, $pageContext->pageId);
    }

    #[Test]
    public function determinesPageIdFromCmdStatementForCopyAction(): void
    {
        // cmd[tt_content][1][copy][target]=2 means copying to page 2
        $request = $this->createRequest()->withQueryParams(['cmd' => ['tt_content' => [1 => ['copy' => ['target' => 2]]]]]);
        $pageContext = $this->processMiddleware($request);
        self::assertSame(2, $pageContext->pageId);
    }

    #[Test]
    public function determinesPageIdFromCmdStatementForCopyActionWithDirectTarget(): void
    {
        // cmd[tt_content][1][copy]=2 means copying to page 2 (direct target format)
        $request = $this->createRequest()->withQueryParams(['cmd' => ['tt_content' => [1 => ['copy' => 2]]]]);
        $pageContext = $this->processMiddleware($request);
        self::assertSame(2, $pageContext->pageId);
    }

    #[Test]
    public function determinesPageIdFromCmdStatementForMoveAction(): void
    {
        // cmd[tt_content][1][move][target]=3 means moving to page 3
        $request = $this->createRequest()->withQueryParams(['cmd' => ['tt_content' => [1 => ['move' => ['target' => 3]]]]]);
        $pageContext = $this->processMiddleware($request);
        self::assertSame(3, $pageContext->pageId);
    }

    #[Test]
    public function fallsBackToZeroWhenNoPageIdCanBeDetermined(): void
    {
        $request = $this->createRequest();
        $pageContext = $this->processMiddleware($request);
        self::assertSame(0, $pageContext->pageId);
    }

    #[Test]
    public function idParameterTakesPrecedenceOverEditStatement(): void
    {
        $request = $this->createRequest()
            ->withQueryParams([
                'id' => 5,
                'edit' => ['pages' => [1 => 'edit']],
            ]);
        $pageContext = $this->processMiddleware($request);
        self::assertSame(5, $pageContext->pageId);
    }

    #[Test]
    public function editStatementTakesPrecedenceOverCmdStatement(): void
    {
        $request = $this->createRequest()
            ->withQueryParams([
                'edit' => ['pages' => [1 => 'edit']],
                'cmd' => ['tt_content' => [1 => ['delete' => 1]]],
            ]);

        $pageContext = $this->processMiddleware($request);
        self::assertSame(1, $pageContext->pageId);
    }

    #[Test]
    public function skipsProcessingWhenRouteDoesNotRequirePageContext(): void
    {
        // Route without requestPageContext option
        $route = new Route('/test', []);

        $request = (new ServerRequest('https://example.com/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', new NullSite())
            ->withAttribute('backend.user', $this->backendUser)
            ->withAttribute('route', $route)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withQueryParams(['id' => 1]);

        $middleware = $this->get(PageContextInitialization::class);

        $handler = new class () implements RequestHandlerInterface {
            public ?PageContext $pageContext = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->pageContext = $request->getAttribute('pageContext');
                return new Response();
            }
        };

        $middleware->process($request, $handler);

        // PageContext should NOT be set
        self::assertNull($handler->pageContext);
    }

    #[Test]
    public function skipsProcessingWhenNoRoutePresent(): void
    {
        $request = (new ServerRequest('https://example.com/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', new NullSite())
            ->withAttribute('backend.user', $this->backendUser)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withQueryParams(['id' => 1]);

        $middleware = $this->get(PageContextInitialization::class);

        $handler = new class () implements RequestHandlerInterface {
            public ?PageContext $pageContext = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->pageContext = $request->getAttribute('pageContext');
                return new Response();
            }
        };

        $middleware->process($request, $handler);

        // PageContext should NOT be set
        self::assertNull($handler->pageContext);
    }

    private function createRequest(): ServerRequestInterface
    {
        // Create a route with requestPageContext option to trigger middleware
        $route = new Route('/test', ['requestPageContext' => true]);

        return (new ServerRequest('https://example.com/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', new NullSite())
            ->withAttribute('backend.user', $this->backendUser)
            ->withAttribute('route', $route)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));
    }

    private function processMiddleware(ServerRequestInterface $request): PageContext
    {
        $middleware = $this->get(PageContextInitialization::class);

        $handler = new class () implements RequestHandlerInterface {
            public ?PageContext $pageContext = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->pageContext = $request->getAttribute('pageContext');
                return new Response();
            }
        };

        $middleware->process($request, $handler);

        self::assertInstanceOf(PageContext::class, $handler->pageContext);
        return $handler->pageContext;
    }
}
