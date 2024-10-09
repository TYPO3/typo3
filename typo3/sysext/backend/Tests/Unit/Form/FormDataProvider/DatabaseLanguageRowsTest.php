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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Form\Exception\DatabaseDefaultLanguageException;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseLanguageRows;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DatabaseLanguageRowsTest extends UnitTestCase
{
    protected DatabaseLanguageRows&MockObject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['BE_USER'] = $this->createMock(BackendUserAuthentication::class);

        $this->subject = $this->getMockBuilder(DatabaseLanguageRows::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRecordWorkspaceOverlay'])
            ->getMock();
    }

    #[Test]
    public function addDataReturnsUnchangedResultIfTableProvidesNoTranslations(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'databaseRow' => [
                'uid' => 42,
                'text' => 'bar',
            ],
            'processedTca' => [
                'ctrl' => [],
                'columns' => [],
            ],
        ];
        self::assertEquals($input, $this->subject->addData($input));
    }

    #[Test]
    public function addDataThrowsExceptionIfDefaultOfLocalizedRecordIsNotFound(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'databaseRow' => [
                'uid' => 42,
                'text' => 'localized text',
                'sys_language_uid' => 2,
                'l10n_parent' => 23,
            ],
            'processedTca' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid',
                    'transOrigPointerField' => 'l10n_parent',
                ],
            ],
        ];

        $this->subject->expects(self::once())->method('getRecordWorkspaceOverlay')->willReturn([]);

        $this->expectException(DatabaseDefaultLanguageException::class);
        $this->expectExceptionCode(1438249426);

        $this->subject->addData($input);
    }

    #[Test]
    public function addDataSetsDefaultLanguageRow(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'databaseRow' => [
                'uid' => 42,
                'text' => 'localized text',
                'sys_language_uid' => 2,
                'l10n_parent' => 23,
            ],
            'processedTca' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid',
                    'transOrigPointerField' => 'l10n_parent',
                ],
            ],
        ];

        $defaultLanguageRow = [
            'uid' => 23,
            'pid' => 123,
            'text' => 'default language text',
            'sys_language_uid' => 0,
        ];

        $this->subject->expects(self::once())->method('getRecordWorkspaceOverlay')->willReturn($defaultLanguageRow);

        $expected = $input;
        $expected['defaultLanguageRow'] = $defaultLanguageRow;

        self::assertEquals($expected, $this->subject->addData($input));
    }

    #[Test]
    public function addDataSetsDiffSourceFieldIfGiven(): void
    {
        $diffSource = [
            'uid' => 42,
            'text' => 'field content of default lang record when lang overlay was created',
        ];

        $input = [
            'tableName' => 'tt_content',
            'databaseRow' => [
                'uid' => 42,
                'text' => 'localized text',
                'sys_language_uid' => 2,
                'l10n_parent' => 23,
                'l10n_diffsource' => json_encode($diffSource),
            ],
            'processedTca' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid',
                    'transOrigPointerField' => 'l10n_parent',
                    'transOrigDiffSourceField' => 'l10n_diffsource',
                ],
            ],
            'defaultLanguageRow' => null,
        ];

        $defaultLanguageRow = [
            'uid' => 23,
            'pid' => 123,
            'text' => 'default language text',
            'sys_language_uid' => 0,
        ];

        $this->subject->expects(self::once())->method('getRecordWorkspaceOverlay')->willReturn($defaultLanguageRow);

        $expected = $input;
        $expected['defaultLanguageRow'] = $defaultLanguageRow;
        $expected['defaultLanguageDiffRow']['tt_content:42'] = $diffSource;

        self::assertEquals($expected, $this->subject->addData($input));
    }

    #[Test]
    public function addDataSetsAdditionalLanguageRowsIfRequestedInUserTypoScript(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'databaseRow' => [
                'uid' => 42,
                'text' => 'localized text',
                'sys_language_uid' => 2,
                'l10n_parent' => 23,
            ],
            'processedTca' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid',
                    'transOrigPointerField' => 'l10n_parent',
                ],
            ],
            'userTsConfig' => [
                'options.' => [
                    'additionalPreviewLanguages' => '3',
                ],
            ],
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'title' => 'Default Language',
                    'iso' => 'DEV',
                ],
                3 => [
                    'uid' => 3,
                    'title' => 'french',
                    'iso' => 'fr',
                ],
            ],
            'defaultLanguageRow' => null,
            'additionalLanguageRows' => [],
        ];

        $translationResult = [
            'translations' => [
                3 => [
                    'uid' => 43,
                    'pid' => 32,
                ],
            ],
        ];
        // For BackendUtility::getRecord()
        $GLOBALS['TCA']['tt_content'] = ['foo'];
        $recordWsolResult = [
            'uid' => 43,
            'pid' => 32,
            'text' => 'localized text in french',
        ];

        $defaultLanguageRow = [
            'uid' => 23,
            'pid' => 32,
            'text' => 'default language text',
            'sys_language_uid' => 0,
        ];

        $translationMock = $this->createMock(TranslationConfigurationProvider::class);
        $translationMock->expects(self::atLeastOnce())->method('translationInfo')
            ->with('tt_content', 23, 3)->willReturn($translationResult);

        $subject = $this->getMockBuilder(DatabaseLanguageRows::class)
            ->setConstructorArgs([$translationMock])
            ->onlyMethods(['getRecordWorkspaceOverlay'])
            ->getMock();

        // The second call is the real check: The "additional overlay" should be fetched
        $series = [
            [['tableName' => 'tt_content', 'uid' => 23], $defaultLanguageRow],
            [['tableName' => 'tt_content', 'uid' => 43], $recordWsolResult],
        ];
        $subject->expects(self::exactly(2))
            ->method('getRecordWorkspaceOverlay')
            ->willReturnCallback(function (string $tableName, int $uid) use (&$series): array {
                [$expectedArgs, $return] = array_shift($series);
                self::assertSame($expectedArgs['tableName'], $tableName);
                self::assertSame($expectedArgs['uid'], $uid);
                return $return;
            });

        $expected = $input;
        $expected['defaultLanguageRow'] = $defaultLanguageRow;
        $expected['additionalLanguageRows'] = [
            3 => [
                'uid' => 43,
                'pid' => 32,
                'text' => 'localized text in french',
            ],
        ];

        self::assertEquals($expected, $subject->addData($input));
    }

    #[Test]
    public function addDataSetsDoesNotAddHandledRowAsAdditionalLanguageRows(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'databaseRow' => [
                'uid' => 42,
                'text' => 'localized text',
                'sys_language_uid' => 2,
                'l10n_parent' => 23,
            ],
            'processedTca' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid',
                    'transOrigPointerField' => 'l10n_parent',
                ],
            ],
            'userTsConfig' => [
                'options.' => [
                    'additionalPreviewLanguages' => '2,3',
                ],
            ],
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'title' => 'Default Language',
                    'iso' => 'DEV',
                ],
                2 => [
                    'uid' => 2,
                    'title' => 'dansk',
                    'iso' => 'dk,',
                ],
                3 => [
                    'uid' => 3,
                    'title' => 'french',
                    'iso' => 'fr',
                ],
            ],
            'defaultLanguageRow' => null,
            'additionalLanguageRows' => [],
        ];

        $translationResult = [
            'translations' => [
                3 => [
                    'uid' => 43,
                ],
            ],
        ];
        // For BackendUtility::getRecord()
        $GLOBALS['TCA']['tt_content'] = ['foo'];
        $recordWsolResult = [
            'uid' => 43,
            'pid' => 32,
            'text' => 'localized text in french',
        ];

        $defaultLanguageRow = [
            'uid' => 23,
            'pid' => 32,
            'text' => 'default language text',
            'sys_language_uid' => 0,
        ];

        $translationMock = $this->createMock(TranslationConfigurationProvider::class);
        $translationMock->expects(self::once())->method('translationInfo')->with('tt_content', 23, 3)
            ->willReturn($translationResult);

        $subject = $this->getMockBuilder(DatabaseLanguageRows::class)
            ->setConstructorArgs([$translationMock])
            ->onlyMethods(['getRecordWorkspaceOverlay'])
            ->getMock();

        // The second call is the real check: The "additional overlay" should be fetched
        $series = [
            [['tableName' => 'tt_content', 'uid' => 23], $defaultLanguageRow],
            [['tableName' => 'tt_content', 'uid' => 43], $recordWsolResult],
        ];
        $subject->expects(self::exactly(2))
            ->method('getRecordWorkspaceOverlay')
            ->willReturnCallback(function (string $tableName, int $uid) use (&$series): array {
                [$expectedArgs, $return] = array_shift($series);
                self::assertSame($expectedArgs['tableName'], $tableName);
                self::assertSame($expectedArgs['uid'], $uid);
                return $return;
            });

        $expected = $input;
        $expected['defaultLanguageRow'] = $defaultLanguageRow;
        $expected['additionalLanguageRows'] = [
            3 => [
                'uid' => 43,
                'pid' => 32,
                'text' => 'localized text in french',
            ],
        ];

        self::assertEquals($expected, $subject->addData($input));
    }

    #[Test]
    public function addDataSetsSourceLanguageRow(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'databaseRow' => [
                'uid' => 42,
                'text' => 'localized text',
                'sys_language_uid' => 3,
                'l10n_parent' => 23,
                'l10n_source' => 24,
            ],
            'processedTca' => [
                'ctrl' => [
                    'languageField' => 'sys_language_uid',
                    'transOrigPointerField' => 'l10n_parent',
                    'translationSource' => 'l10n_source',
                ],
            ],
            'systemLanguageRows' => [
                0 => [
                    'uid' => 0,
                    'title' => 'Default Language',
                    'iso' => 'DEV',
                ],
                2 => [
                    'uid' => 2,
                    'title' => 'dansk',
                    'iso' => 'dk,',
                ],
                3 => [
                    'uid' => 3,
                    'title' => 'french',
                    'iso' => 'fr',
                ],
            ],
            'defaultLanguageRow' => null,
            'sourceLanguageRow' => null,
            'additionalLanguageRows' => [],
        ];

        // For BackendUtility::getRecord()
        $GLOBALS['TCA']['tt_content'] = ['foo'];
        $sourceLanguageRow = [
            'uid' => 24,
            'pid' => 32,
            'text' => 'localized text in dank',
            'sys_language_uid' => 2,
        ];
        $defaultLanguageRow = [
            'uid' => 23,
            'pid' => 32,
            'text' => 'default language text',
            'sys_language_uid' => 0,
        ];

        $series = [
            [['tableName' => 'tt_content', 'uid' => 23], $defaultLanguageRow],
            [['tableName' => 'tt_content', 'uid' => 24], $sourceLanguageRow],
        ];
        $this->subject->expects(self::exactly(2))
            ->method('getRecordWorkspaceOverlay')
            ->willReturnCallback(function (string $tableName, int $uid) use (&$series): array {
                [$expectedArgs, $return] = array_shift($series);
                self::assertSame($expectedArgs['tableName'], $tableName);
                self::assertSame($expectedArgs['uid'], $uid);
                return $return;
            });

        $expected = $input;
        $expected['defaultLanguageRow'] = $defaultLanguageRow;
        $expected['sourceLanguageRow'] = $sourceLanguageRow;

        self::assertEquals($expected, $this->subject->addData($input));
    }
}
