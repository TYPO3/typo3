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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests the TCA option "min".
 */
class MinValueTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/MinValuePages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);

        Bootstrap::initializeLanguageObject();
    }

    public function valuesLowerThanMinResetToEmptyStringDataProvider(): iterable
    {
        yield 'Too few characters result in empty string' => [
            'value' => 'Too short',
            'expected' => '',
        ];

        yield 'More than "min" characters stay the same' => [
            'value' => 'This has enough length',
            'expected' => 'This has enough length',
        ];

        yield 'With unicode exact chars stays the same' => [
            'value' => "123456789\u{1F421}",
            'expected' => "123456789\u{1F421}",
        ];

        yield 'With unicode too few chars results in empty' => [
            'value' => "12345678\u{1F421}",
            'expected' => '',
        ];
    }

    /**
     * @test
     * @dataProvider valuesLowerThanMinResetToEmptyStringDataProvider
     */
    public function valuesLowerThanMinResetToEmptyString(string $string, string $expected): void
    {
        // Should work for type=input and type=text (except RTE).
        $actionService = new ActionService();
        $map = $actionService->createNewRecord('tt_content', 1, [
            'tx_testdatahandler_input_minvalue' => $string,
            'tx_testdatahandler_text_minvalue' => $string,
        ]);
        $newRecordId = reset($map['tt_content']);
        $newRecord = BackendUtility::getRecord('tt_content', $newRecordId);
        self::assertEquals($expected, $newRecord['tx_testdatahandler_input_minvalue']);
        self::assertEquals($expected, $newRecord['tx_testdatahandler_text_minvalue']);
    }

    /**
     * @test
     */
    public function minDoesNotWorkForRTE(): void
    {
        $actionService = new ActionService();
        $map = $actionService->createNewRecord('tt_content', 1, [
            'tx_testdatahandler_richttext_minvalue' => 'Not working',
        ]);
        $newRecordId = reset($map['tt_content']);
        $newRecord = BackendUtility::getRecord('tt_content', $newRecordId);
        self::assertEquals('Not working', $newRecord['tx_testdatahandler_richttext_minvalue']);
    }

    /**
     * @test
     */
    public function minValueZeroIsIgnored(): void
    {
        $actionService = new ActionService();
        $map = $actionService->createNewRecord('tt_content', 1, [
            'tx_testdatahandler_input_minvalue_zero' => 'test123',
        ]);
        $newRecordId = reset($map['tt_content']);
        $newRecord = BackendUtility::getRecord('tt_content', $newRecordId);
        self::assertEquals('test123', $newRecord['tx_testdatahandler_input_minvalue_zero']);
    }
}
