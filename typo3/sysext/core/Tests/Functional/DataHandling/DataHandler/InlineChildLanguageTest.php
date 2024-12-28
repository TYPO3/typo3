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
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class InlineChildLanguageTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_csv',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_mm',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguage.csv');
    }

    #[Test]
    public function childrenFollowStoredLanguageOfNewParent(): void
    {
        $dataHandler = $this->processDataMapAsUser(3, [
            'tx_testirrecsv_hotel' => ['NEW1' => ['pid' => 1, 'title' => 'Hotel #2', 'sys_language_uid' => -1, 'offers' => 'NEW2']],
            'tx_testirrecsv_offer' => ['NEW2' => ['pid' => 1, 'title' => 'Offer #2', 'sys_language_uid' => -1]],
        ]);

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageOfNewParentWithoutLanguageAccess.csv');
    }

    #[Test]
    public function childrenOfMmInlineRelationKeepTheirLanguage(): void
    {
        $dataHandler = $this->processDataMapAsUser(1, ['tx_testirremm_hotel' => [1 => ['sys_language_uid' => -1]]]);

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageOfMmRelation.csv');
    }

    #[Test]
    public function childrenOfCyclicInlineRelationAreAligned(): void
    {
        $GLOBALS['TCA']['tx_testirrecsv_offer']['columns']['prices']['config']['foreign_table'] = 'tx_testirrecsv_hotel';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $dataHandler = $this->processDataMapAsUser(1, ['tx_testirrecsv_hotel' => [1 => ['sys_language_uid' => -1]]]);

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageOfCyclicRelation.csv');
    }

    #[Test]
    public function editorWithoutAccessToChildLanguageFieldChangesLanguageOfChildren(): void
    {
        $dataHandler = $this->processDataMapAsUser(2, ['tt_content' => [1 => ['sys_language_uid' => 0]]]);

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageChanged.csv');
    }

    #[Test]
    public function editorWithoutAccessToChildLanguageFieldChangesLanguageOfChildrenInWorkspace(): void
    {
        $dataHandler = $this->processDataMapAsUser(2, ['tt_content' => [1 => ['sys_language_uid' => 0]]], 1);

        self::assertSame([], $dataHandler->errorLog);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageChangedInWorkspace.csv');
    }

    #[Test]
    public function editorWithoutAccessToChildTableChangesLanguageOfChildrenInWorkspace(): void
    {
        $this->processDataMapAsUser(4, ['tt_content' => [1 => ['sys_language_uid' => 0]]], 1);

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/InlineChildLanguageChangedInWorkspace.csv');
    }

    private function processDataMapAsUser(int $userId, array $dataMap, int $workspaceId = 0): DataHandler
    {
        $backendUser = $this->setUpBackendUser($userId);
        $backendUser->setWorkspace($workspaceId);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start($dataMap, [], $backendUser);
        $dataHandler->process_datamap();
        return $dataHandler;
    }
}
