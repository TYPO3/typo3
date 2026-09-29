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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MoveUniqueFieldsHistoryTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->importCSVDataSet(__DIR__ . '/DataSet/MoveIntoAnotherSite.csv');
        $this->writeSiteConfiguration('main', $this->buildSiteConfiguration(1, 'https://main.example/'), [$this->buildDefaultLanguageConfiguration('EN', '/')]);
        $this->writeSiteConfiguration('second', $this->buildSiteConfiguration(10, 'https://second.example/'), [$this->buildDefaultLanguageConfiguration('EN', '/')]);
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function slugsOfSubpagesMadeUniqueInTheTargetSiteAreWrittenToTheHistoryOfTheMove(): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['pages' => [2 => ['move' => 10]]]);
        $dataHandler->process_cmdmap();
        $scope = $dataHandler->getCorrelationId()->getScope();

        $slug = $this->getConnectionPool()->getConnectionForTable('pages')
            ->select(['slug'], 'pages', ['uid' => 3])->fetchOne();
        self::assertSame('/team-1', $slug);
        $entries = $this->getConnectionPool()->getConnectionForTable('sys_history')
            ->select(['history_data', 'correlation_id'], 'sys_history', ['tablename' => 'pages', 'recuid' => 3, 'actiontype' => RecordHistoryStore::ACTION_MODIFY])
            ->fetchAllAssociative();
        self::assertCount(1, $entries);
        self::assertSame(['oldRecord' => ['slug' => '/team'], 'newRecord' => ['slug' => '/team-1']], json_decode($entries[0]['history_data'], true));
        self::assertSame($scope, CorrelationId::fromString($entries[0]['correlation_id'])->getScope());
    }

    #[Test]
    public function changesOfAnEarlierDatamapAreNotWrittenToTheHistoryAgainWhenAMoveMakesSlugsUnique(): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['pages' => [3 => ['title' => 'Team edited']]], ['pages' => [2 => ['move' => 10]]]);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        $entries = $this->getConnectionPool()->getConnectionForTable('sys_history')
            ->select(['history_data'], 'sys_history', ['tablename' => 'pages', 'recuid' => 3, 'actiontype' => RecordHistoryStore::ACTION_MODIFY], [], ['uid' => 'ASC'])
            ->fetchFirstColumn();
        self::assertCount(2, $entries);
        self::assertSame(['title' => 'Team edited'], array_intersect_key(json_decode($entries[0], true)['newRecord'], ['title' => true]));
        self::assertSame(['oldRecord' => ['slug' => '/team'], 'newRecord' => ['slug' => '/team-1']], json_decode($entries[1], true));
    }
}
