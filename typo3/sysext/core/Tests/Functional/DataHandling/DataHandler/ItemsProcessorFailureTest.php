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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * An itemsProcFunc is arbitrary code, and one written for the editing form of its own table
 * may well refuse to run anywhere else. DataHandler composes the items of a check and of a
 * radio field itself, so it meets that refusal on every write and has to survive it.
 */
final class ItemsProcessorFailureTest extends FunctionalTestCase
{
    private const PAGE_ID = 200;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/ItemsProcessorFailurePages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)
            ->createFromUserPreferences($this->setUpBackendUser(1));
    }

    #[Test]
    public function checkboxComposingItsOnlyBoxIsWritten(): void
    {
        // The field declares no "items" key at all and leaves the single box to its
        // processor, which is a configuration DataHandler has to read before it runs it.
        $map = (new ActionService())->createNewRecord('tt_content', self::PAGE_ID, [
            'tx_testdatahandler_checkbox_undefined_items' => 1,
        ]);
        $record = BackendUtility::getRecord('tt_content', reset($map['tt_content']));

        self::assertSame(1, (int)$record['tx_testdatahandler_checkbox_undefined_items']);
    }

    #[Test]
    public function checkboxKeepsItsDeclaredBoxesWhereTheProcessorRefusesToRun(): void
    {
        // One declared box, so the bitmask may hold 1 and nothing beyond it. Without the
        // declared boxes to fall back on, writing the record would fail altogether.
        $map = (new ActionService())->createNewRecord('tt_content', self::PAGE_ID, [
            'tx_testdatahandler_checkbox_failing_items' => 1,
        ]);
        $record = BackendUtility::getRecord('tt_content', reset($map['tt_content']));

        self::assertSame(1, (int)$record['tx_testdatahandler_checkbox_failing_items']);
    }

    #[Test]
    public function checkboxMasksAValueBeyondItsDeclaredBoxesWhereTheProcessorRefusesToRun(): void
    {
        $map = (new ActionService())->createNewRecord('tt_content', self::PAGE_ID, [
            'tx_testdatahandler_checkbox_failing_items' => 3,
        ]);
        $record = BackendUtility::getRecord('tt_content', reset($map['tt_content']));

        self::assertSame(1, (int)$record['tx_testdatahandler_checkbox_failing_items']);
    }

    #[Test]
    public function radioWritesADeclaredValueWhereTheProcessorRefusesToRun(): void
    {
        // The declared items are compared before the processor is run, so this value never
        // needs it.
        $map = (new ActionService())->createNewRecord('tt_content', self::PAGE_ID, [
            'tx_testdatahandler_radio_failing_items' => 'predefined value',
        ]);
        $record = BackendUtility::getRecord('tt_content', reset($map['tt_content']));

        self::assertSame('predefined value', $record['tx_testdatahandler_radio_failing_items']);
    }

    #[Test]
    public function radioDropsAnUndeclaredValueWhereTheProcessorRefusesToRun(): void
    {
        // Only the processor could have named this value, and it did not run, so the field
        // keeps its default rather than the write failing.
        $map = (new ActionService())->createNewRecord('tt_content', self::PAGE_ID, [
            'tx_testdatahandler_radio_failing_items' => 'processed value',
        ]);
        $record = BackendUtility::getRecord('tt_content', reset($map['tt_content']));

        self::assertSame('', $record['tx_testdatahandler_radio_failing_items']);
    }
}
