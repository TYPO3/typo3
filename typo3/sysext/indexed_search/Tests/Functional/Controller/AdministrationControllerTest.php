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

namespace TYPO3\CMS\IndexedSearch\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Middleware\BackendModuleValidator;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AdministrationControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'indexed_search',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../../../core/Tests/Functional/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../../../../core/Tests/Functional/Fixtures/pages.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function moduleEntryPointRendersFirstActionByDefault(): void
    {
        self::assertStringContainsString('<h1>Detailed statistics</h1>', $this->dispatch('manage_search_index'));
    }

    #[Test]
    public function moduleEntryPointRestoresLastSelectedMenuAction(): void
    {
        self::assertStringContainsString('<h1>List of indexed pages</h1>', $this->dispatch('manage_search_index.Administration_pages'));
        self::assertStringContainsString('<h1>List of indexed pages</h1>', $this->dispatch('manage_search_index', ['id' => 1]));
        self::assertSame('pages', $GLOBALS['BE_USER']->getModuleData('manage_search_index')['menuAction']);
    }

    #[Test]
    public function explicitlyRequestedActionTakesPrecedenceOverRememberedAction(): void
    {
        $this->dispatch('manage_search_index.Administration_pages');
        self::assertStringContainsString('<h1>General statistics</h1>', $this->dispatch('manage_search_index', ['action' => 'index']));
        self::assertStringContainsString('<h1>General statistics</h1>', $this->dispatch('manage_search_index'));
    }

    #[Test]
    public function detailActionIsNotRemembered(): void
    {
        $this->dispatch('manage_search_index.Administration_externalDocuments');
        $this->dispatch('manage_search_index.Administration_statisticDetails', ['pageHash' => 'a1b2c3']);
        self::assertStringContainsString('<h1>List of indexed external documents</h1>', $this->dispatch('manage_search_index', ['id' => 1]));
    }

    #[Test]
    public function detailActionRequestedViaArgumentIsNotRemembered(): void
    {
        $this->dispatch('manage_search_index.Administration_externalDocuments');
        $this->dispatch('manage_search_index', ['action' => 'statisticDetails', 'pageHash' => 'a1b2c3']);
        self::assertStringContainsString('<h1>List of indexed external documents</h1>', $this->dispatch('manage_search_index', ['id' => 1]));
    }

    #[Test]
    public function invalidRememberedActionFallsBackToFirstAction(): void
    {
        $GLOBALS['BE_USER']->pushModuleData('manage_search_index', ['menuAction' => 'deleteIndexedItem']);
        self::assertStringContainsString('<h1>Detailed statistics</h1>', $this->dispatch('manage_search_index'));
    }

    private function dispatch(string $routeIdentifier, array $queryParams = []): string
    {
        $route = Route::fromSymfonyRoute($this->get(Router::class)->getRoute($routeIdentifier), $routeIdentifier);
        $request = new ServerRequest('https://localhost/typo3/', 'GET')
            ->withQueryParams($queryParams)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $route);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $handler = new class ($this->get(Bootstrap::class)) implements RequestHandlerInterface {
            public function __construct(private readonly Bootstrap $bootstrap) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $GLOBALS['TYPO3_REQUEST'] = $request;
                return $this->bootstrap->handleBackendRequest($request);
            }
        };
        return (string)$this->get(BackendModuleValidator::class)->process($request, $handler)->getBody();
    }
}
