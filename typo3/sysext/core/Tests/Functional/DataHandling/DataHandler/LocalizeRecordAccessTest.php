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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LocalizeRecordAccessTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DA' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'da_DK.UTF8'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_foreignfield',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/LocalizeRecordAccess/Import.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
            ],
        );
    }

    public static function localizationCommandDataProvider(): array
    {
        return [
            'localize' => ['localize'],
            'copyToLanguage' => ['copyToLanguage'],
        ];
    }

    #[Test]
    #[DataProvider('localizationCommandDataProvider')]
    public function recordWithDeniedRecordTypeIsNotLocalizedAndLeavesNoChildRecordsBehind(string $command): void
    {
        $dataHandler = $this->executeCommandAsEditor($command);

        self::assertNotEmpty($dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/LocalizeRecordAccess/DeniedRecordTypeIsNotLocalized.csv');
    }

    public static function allowedLocalizationCommandDataProvider(): array
    {
        return [
            'localize' => ['localize', 'AllowedRecordTypeIsLocalized.csv'],
            'copyToLanguage' => ['copyToLanguage', 'AllowedRecordTypeIsCopiedToLanguage.csv'],
        ];
    }

    #[Test]
    #[DataProvider('allowedLocalizationCommandDataProvider')]
    public function recordWithAllowedRecordTypeIsLocalizedWithChildRecords(string $command, string $expectedDataSet): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('be_groups')
            ->update('be_groups', ['explicit_allowdeny' => 'tt_content:CType:text,tt_content:CType:textmedia'], ['uid' => 9]);

        $dataHandler = $this->executeCommandAsEditor($command);

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/LocalizeRecordAccess/' . $expectedDataSet);
    }

    #[Test]
    public function childRecordOfNotModifiableTableIsNotLocalizedAndLeavesNoGrandchildRecordsBehind(): void
    {
        $this->executeCommandAsEditor('localize', 110);

        $this->assertCSVDataSet(__DIR__ . '/DataSet/LocalizeRecordAccess/NotModifiableChildTableIsNotLocalized.csv');
    }

    #[Test]
    public function recordOnPageWithoutContentEditPermissionIsNotLocalizedAndLeavesNoChildRecordsBehind(): void
    {
        $connectionPool = $this->get(ConnectionPool::class);
        $connectionPool->getConnectionForTable('be_groups')
            ->update('be_groups', ['explicit_allowdeny' => 'tt_content:CType:text,tt_content:CType:textmedia'], ['uid' => 9]);
        $connectionPool->getConnectionForTable('pages')->update('pages', ['perms_group' => 1], ['uid' => 1]);
        $connectionPool->getConnectionForTable('sys_file_reference')->update('sys_file_reference', ['pid' => 3], ['uid' => 10]);

        $dataHandler = $this->executeCommandAsEditor('localize');

        self::assertNotEmpty($dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/LocalizeRecordAccess/NotInsertablePageIsNotLocalized.csv');
    }

    private function executeCommandAsEditor(string $command, int $uid = 100): DataHandler
    {
        $backendUser = $this->setUpBackendUser(2);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['tt_content' => [$uid => [$command => 1]]]);
        $dataHandler->process_cmdmap();
        return $dataHandler;
    }
}
