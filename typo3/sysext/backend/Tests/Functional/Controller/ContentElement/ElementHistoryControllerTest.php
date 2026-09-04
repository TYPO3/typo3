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

    protected const array LANGUAGE_PRESETS = [
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
        $request = new ServerRequest('https://example.com/typo3/record/history', 'GET')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $this->get(Router::class)->getRoute('record_history'))
            ->withQueryParams(['element' => $element]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        return (string)$this->get(ElementHistoryController::class)->mainAction($request)->getBody();
    }

    private function renderHistoryWithSettings(string $element, array $settings): string
    {
        $request = new ServerRequest('https://example.com/typo3/record/history', 'POST')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $this->get(Router::class)->getRoute('record_history'))
            ->withQueryParams(['element' => $element])
            ->withParsedBody(['settings' => $settings]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        return (string)$this->get(ElementHistoryController::class)->mainAction($request)->getBody();
    }

    /**
     * @return string[] titles of the pages the operation tests work with
     */
    private function getPageTitles(): array
    {
        return $this->getConnectionPool()
            ->getConnectionForTable('pages')
            ->executeQuery('SELECT title FROM pages WHERE uid IN (1, 2) ORDER BY uid')
            ->fetchFirstColumn();
    }

    private function getPageId(int $uid): int
    {
        return (int)$this->getConnectionPool()
            ->getConnectionForTable('pages')
            ->executeQuery('SELECT pid FROM pages WHERE uid = ?', [$uid])
            ->fetchOne();
    }

    private function undoOperation(string $scope, string $method = 'POST'): void
    {
        $request = $this->buildRollbackRequest($method, 0)->withQueryParams(['element' => 'pages:1']);
        $request = $method === 'POST'
            ? $request->withParsedBody(['rollbackScope' => $scope])
            : $request->withQueryParams(['element' => 'pages:1', 'rollbackScope' => $scope]);
        $this->get(ElementHistoryController::class)->mainAction($request);
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
        $request = new ServerRequest('https://example.com/typo3/record/history', $method)
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

    #[Test]
    public function rollbackPreviewAnnouncesThatARecordIsMovedBack(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ElementHistoryMoveAndStage.csv');
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['pages' => [4 => ['move' => 3]]]);
        $dataHandler->process_cmdmap();
        $historyEntry = (int)$this->getConnectionPool()
            ->getConnectionForTable('sys_history')
            ->executeQuery('SELECT MAX(uid) FROM sys_history')
            ->fetchOne();

        $request = $this->buildRollbackRequest('GET', $historyEntry)
            ->withQueryParams(['element' => 'pages:4', 'historyEntry' => $historyEntry]);
        $body = (string)$this->get(ElementHistoryController::class)->mainAction($request)->getBody();

        // Without this the preview claims there is nothing to roll back, while there is
        self::assertStringContainsString('will be moved back to Source page', $body);
        self::assertStringNotContainsString('There are no differences', $body);
    }

    #[Test]
    public function operationGroupsAreNotShownByDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ElementHistoryOperations.csv');

        self::assertStringNotContainsString('This operation changed', $this->renderHistoryOfRootPage());
    }

    #[Test]
    public function operationGroupsCountTheRecordsOfTheWholeOperation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ElementHistoryOperations.csv');

        $body = $this->renderHistoryWithSettings('pages:1', ['groupByOperation' => 1, 'showSubElements' => 1]);

        // "op1" touched three pages, "op2" two of them, "aaa" of the base data set only one
        self::assertStringContainsString('This operation changed 3 records', $body);
        self::assertStringContainsString('This operation changed 2 records', $body);
        self::assertStringContainsString('This operation changed 1 record', $body);
    }

    #[Test]
    public function operationGroupsPutTheEntriesOfOneOperationNextToEachOther(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ElementHistoryOperations.csv');

        $body = $this->renderHistoryWithSettings('pages:1', ['groupByOperation' => 1, 'showSubElements' => 1]);

        // The changelog is sorted by time, and both operations wrote all their entries in the
        // same second, so they arrive interleaved. Three operations must still yield three
        // headers, not one per switch between them.
        self::assertSame(3, substr_count($body, 'This operation changed'));
    }

    #[Test]
    public function undoingAnOperationRollsBackEveryRecordItChanged(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ElementHistoryOperations.csv');
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['pages' => [1 => ['title' => 'Changed root'], 2 => ['title' => 'Changed child']]], []);
        $dataHandler->process_datamap();
        self::assertSame(['Changed root', 'Changed child'], $this->getPageTitles());

        $this->undoOperation((string)$dataHandler->getCorrelationId()->getScope());

        self::assertSame(['Root', 'Child A'], $this->getPageTitles());
    }

    #[Test]
    public function undoingAnOperationAlsoMovesItsRecordsBack(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ElementHistoryOperations.csv');
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(
            ['pages' => [2 => ['title' => 'Changed child']]],
            ['pages' => [3 => ['move' => 2]]]
        );
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();
        self::assertSame(2, $this->getPageId(3));

        // Both runs belong to the same DataHandler, so they share one correlation scope
        $this->undoOperation((string)$dataHandler->getCorrelationId()->getScope());

        self::assertSame(['Root', 'Child A'], $this->getPageTitles());
        self::assertSame(1, $this->getPageId(3));
    }

    #[Test]
    public function undoingAnOperationIsNotPerformedOnGetRequest(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ElementHistoryOperations.csv');
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['pages' => [1 => ['title' => 'Changed root']]], []);
        $dataHandler->process_datamap();

        $this->undoOperation((string)$dataHandler->getCorrelationId()->getScope(), 'GET');

        self::assertSame('Changed root', $this->getPageTitles()[0]);
    }

    #[Test]
    public function operationGroupsOfferToUndoTheWholeOperation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ElementHistoryOperations.csv');

        $body = $this->renderHistoryWithSettings('pages:1', ['groupByOperation' => 1, 'showSubElements' => 1]);

        self::assertStringContainsString('Undo this operation', $body);
        self::assertStringContainsString('name="rollbackScope"', $body);
        self::assertStringContainsString('value="op1"', $body);
    }
}
