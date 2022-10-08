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

use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Form\Exception\DatabaseDefaultLanguageException;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseLanguageRows;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DatabaseLanguageRowsTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var DatabaseLanguageRows|MockObject
     */
    protected MockObject $subject;

    /** @var ObjectProphecy<BackendUserAuthentication> */
    protected ObjectProphecy $beUserProphecy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $this->beUserProphecy;

        $this->subject = $this->getMockBuilder(DatabaseLanguageRows::class)
            ->onlyMethods(['getRecordWorkspaceOverlay'])
            ->getMock();
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

        $translationProphecy = $this->prophesize(TranslationConfigurationProvider::class);
        GeneralUtility::addInstance(TranslationConfigurationProvider::class, $translationProphecy->reveal());
        $translationProphecy->translationInfo('tt_content', 23, 3)->shouldBeCalled()->willReturn($translationResult);

        // The second call is the real check: The "additional overlay" should be fetched
        $this->subject->expects(self::exactly(2))
            ->method('getRecordWorkspaceOverlay')
            ->withConsecutive(['tt_content', 23], ['tt_content', 43])
            ->willReturnOnConsecutiveCalls($defaultLanguageRow, $recordWsolResult);

        $expected = $input;
        $expected['defaultLanguageRow'] = $defaultLanguageRow;
        $expected['additionalLanguageRows'] = [
            3 => [
                'uid' => 43,
                'pid' => 32,
                'text' => 'localized text in french',
            ],
        ];

        self::assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
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

        $translationProphecy = $this->prophesize(TranslationConfigurationProvider::class);
        GeneralUtility::addInstance(TranslationConfigurationProvider::class, $translationProphecy->reveal());
        $translationProphecy->translationInfo('tt_content', 23, 3)->shouldBeCalled()->willReturn($translationResult);
        $translationProphecy->translationInfo('tt_content', 23, 2)->shouldNotBeCalled();

        // The second call is the real check: The "additional overlay" should be fetched
        $this->subject->expects(self::exactly(2))
            ->method('getRecordWorkspaceOverlay')
            ->withConsecutive(['tt_content', 23], ['tt_content', 43])
            ->willReturnOnConsecutiveCalls($defaultLanguageRow, $recordWsolResult);

        $expected = $input;
        $expected['defaultLanguageRow'] = $defaultLanguageRow;
        $expected['additionalLanguageRows'] = [
            3 => [
                'uid' => 43,
                'pid' => 32,
                'text' => 'localized text in french',
            ],
        ];

        self::assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
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
        $this->subject->expects(self::exactly(2))
            ->method('getRecordWorkspaceOverlay')
            ->withConsecutive(['tt_content', 23], ['tt_content', 24])
            ->willReturn($defaultLanguageRow, $sourceLanguageRow);

        $expected = $input;
        $expected['defaultLanguageRow'] = $defaultLanguageRow;
        $expected['sourceLanguageRow'] = $sourceLanguageRow;

        self::assertEquals($expected, $this->subject->addData($input));
    }
}
