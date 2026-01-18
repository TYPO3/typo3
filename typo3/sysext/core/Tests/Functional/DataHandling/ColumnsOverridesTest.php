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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for columnsOverrides support in DataHandler.
 *
 * These tests verify that DataHandler respects type-specific TCA columnsOverrides
 * during copy, move, delete, and other operations.
 */
final class ColumnsOverridesTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DA' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'da_DK.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users_admin.csv');
        $this->importCSVDataSet(__DIR__ . '/DataSet/ColumnsOverridesPages.csv');
        $this->writeSiteConfiguration(
            'test-site',
            $this->buildSiteConfiguration(1, 'https://test.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
            ],
        );
    }

    #[Test]
    public function copyRecordRespectsColumnsOverrides(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Set up TCA with type-specific columnsOverrides
        $GLOBALS['TCA']['tt_content']['types']['text']['columnsOverrides'] = [
            'bodytext' => [
                'config' => [
                    'min' => 10,
                ],
            ],
        ];
        $GLOBALS['TCA']['tt_content']['types']['textpic']['columnsOverrides'] = [
            'bodytext' => [
                'config' => [
                    'min' => 30,
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        // Create record of type 'text'
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tt_content' => [
                'NEW1' => [
                    'pid' => 1,
                    'CType' => 'text',
                    'header' => 'Test Content',
                    'bodytext' => "Line 1\nLine 2",
                ],
            ],
        ], [], $backendUser);
        $dataHandler->process_datamap();
        $sourceUid = $dataHandler->substNEWwithIDs['NEW1'];

        // Copy the record
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [
            'tt_content' => [
                $sourceUid => [
                    'copy' => [
                        'action' => 'paste',
                        'target' => 1,
                        'update' => [
                            'CType' => 'textpic',
                            'bodytext' => "Line 1\nLine 2\nLine 3",
                        ],
                    ],
                ],
            ],
        ], $backendUser);
        $dataHandler->process_cmdmap();

        $copiedUid = $dataHandler->copyMappingArray['tt_content'][$sourceUid] ?? null;

        $record1 = BackendUtility::getRecord('tt_content', $sourceUid);
        $copiedRecord = BackendUtility::getRecord('tt_content', $copiedUid);
        self::assertEquals("Line 1\nLine 2", $record1['bodytext'], 'Record 1 is added with correct input');
        self::assertEquals('textpic', $copiedRecord['CType'], 'Copied record has a different CType');
        self::assertEquals('', $copiedRecord['bodytext'], 'Copied record has no bodytext as there are not enough characters');
    }

    #[Test]
    public function fixUniqueInSiteRespectsColumnsOverridesForSlugFields(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Set up type-specific slug configuration via columnsOverrides
        // doktype 1 (standard page) uses author for slug generation
        $GLOBALS['TCA']['pages']['types']['1']['columnsOverrides'] = [
            'slug' => [
                'config' => [
                    'generatorOptions' => [
                        'fields' => ['author'],
                        'fieldSeparator' => '-',
                        'prefixParentPageSlug' => true,
                    ],
                    'eval' => 'uniqueInSite',
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        // Create a page with author
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'pages' => [
                'NEW1' => [
                    'pid' => 1,
                    'doktype' => 1,
                    'title' => 'Page Title',
                    'author' => 'Benni',
                ],
            ],
        ], [], $backendUser);
        $dataHandler->process_datamap();

        $pageUid = $dataHandler->substNEWwithIDs['NEW1'];
        self::assertIsInt($pageUid);

        $pageRecord = BackendUtility::getRecord('pages', $pageUid);
        // The slug should have been processed considering the columnsOverrides
        self::assertEquals('/benni', $pageRecord['slug']);
    }

    #[Test]
    public function newFieldArrayRespectsColumnsOverridesDefaults(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Set up columnsOverrides with type-specific default
        $GLOBALS['TCA']['tt_content']['types']['textmedia']['columnsOverrides'] = [
            'header_layout' => [
                'config' => [
                    'default' => 5,
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        // Create a textmedia content element
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tt_content' => [
                'NEW1' => [
                    'pid' => 1,
                    'CType' => 'textmedia',
                    'header' => 'Test with columnsOverrides default',
                ],
            ],
        ], [], $backendUser);
        $dataHandler->process_datamap();

        $newUid = $dataHandler->substNEWwithIDs['NEW1'];
        $record = BackendUtility::getRecord('tt_content', $newUid);

        // The columnsOverrides default should have been applied
        self::assertEquals(5, $record['header_layout']);
    }
}
