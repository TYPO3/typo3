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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller\ContentElement;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\ContentElement\ElementHistoryController;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ElementHistoryControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ElementHistoryUserTypes.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->writeSiteConfiguration('main', $this->buildSiteConfiguration(1, '/'));
    }

    private function renderHistoryOfRootPage(): string
    {
        return $this->renderHistoryOf('pages:1');
    }

    private function renderHistoryOf(string $element): string
    {
        $request = (new ServerRequest('https://example.com/typo3/record/history', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $this->get(Router::class)->getRoute('record_history'))
            ->withQueryParams(['element' => $element]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        return (string)$this->get(ElementHistoryController::class)->mainAction($request)->getBody();
    }

    #[Test]
    public function backendUserIsShownForBackendEntries(): void
    {
        self::assertStringContainsString('Administrator', $this->renderHistoryOfRootPage());
    }

    #[Test]
    public function frontendUserIsNotShownAsBackendUser(): void
    {
        $body = $this->renderHistoryOfRootPage();

        // The frontend user uid 5 must not be attributed to backend user uid 5
        self::assertStringNotContainsString('editorbob', $body);
        self::assertStringNotContainsString('Editor Bob', $body);
        self::assertStringContainsString('Frontend user', $body);
    }

    /**
     * Modifies pages:1 and returns the uid of the history entry a rollback starts from.
     */
    private function modifyRootPage(): int
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['pages' => [1 => ['title' => 'Changed title']]], []);
        $dataHandler->process_datamap();

        return (int)$this->getConnectionPool()
            ->getConnectionForTable('sys_history')
            ->executeQuery('SELECT MAX(uid) FROM sys_history')
            ->fetchOne();
    }

    private function getRootPageTitle(): string
    {
        return (string)$this->getConnectionPool()
            ->getConnectionForTable('pages')
            ->executeQuery('SELECT title FROM pages WHERE uid = 1')
            ->fetchOne();
    }

    private function buildRollbackRequest(string $method, int $historyEntry): ServerRequest
    {
        $request = (new ServerRequest('https://example.com/typo3/record/history', $method))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $this->get(Router::class)->getRoute('record_history'))
            ->withQueryParams(['element' => 'pages:1', 'historyEntry' => $historyEntry]);
        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    #[Test]
    public function rollbackIsPerformedOnPostRequest(): void
    {
        $historyEntry = $this->modifyRootPage();
        self::assertSame('Changed title', $this->getRootPageTitle());

        $request = $this->buildRollbackRequest('POST', $historyEntry)
            ->withParsedBody(['rollbackFields' => 'pages:1']);
        $this->get(ElementHistoryController::class)->mainAction($request);

        self::assertSame('Root', $this->getRootPageTitle());
    }

    #[Test]
    public function rollbackIsNotPerformedOnGetRequest(): void
    {
        $historyEntry = $this->modifyRootPage();

        $request = $this->buildRollbackRequest('GET', $historyEntry)
            ->withQueryParams(['element' => 'pages:1', 'historyEntry' => $historyEntry, 'rollbackFields' => 'pages:1']);
        $this->get(ElementHistoryController::class)->mainAction($request);

        self::assertSame('Changed title', $this->getRootPageTitle());
    }

    #[Test]
    public function moveShowsSourceAndTargetPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ElementHistoryMoveAndStage.csv');
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['pages' => [4 => ['move' => 3]]]);
        $dataHandler->process_cmdmap();

        $body = $this->renderHistoryOf('pages:4');

        self::assertStringContainsString('Source page', $body);
        self::assertStringContainsString('Target page', $body);
    }

    #[Test]
    public function stageChangeShowsItsComment(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ElementHistoryMoveAndStage.csv');

        self::assertStringContainsString('Please review this page', $this->renderHistoryOfRootPage());
    }
}
