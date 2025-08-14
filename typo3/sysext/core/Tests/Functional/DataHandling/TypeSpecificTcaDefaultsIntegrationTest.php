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
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Integration tests for type-specific TCAdefaults in DataHandler contexts
 */
final class TypeSpecificTcaDefaultsIntegrationTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    private ActionService $actionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users_admin.csv');
        $this->importCSVDataSet(__DIR__ . '/DataHandler/DataSet/LiveDefaultPages.csv');
        $this->setUpLanguageSites();
        $this->actionService = new ActionService();
    }

    private function setUpLanguageSites(): void
    {
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
        );
    }

    #[Test]
    public function dataHandlerAppliesTypeSpecificDefaults(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Set up page TSconfig with type-specific defaults
        $this->actionService->modifyRecord('pages', 88, [
            'TSconfig' => chr(10) .
                'TCAdefaults.tt_content.header_layout = 1' . chr(10) .
                'TCAdefaults.tt_content.header_layout.types.textmedia = 3' . chr(10) .
                'TCAdefaults.tt_content.frame_class = default' . chr(10) .
                'TCAdefaults.tt_content.frame_class.types.textmedia = ruler-before',
        ]);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $datamap = [
            'tt_content' => [
                'NEW123' => [
                    'pid' => 88,
                    'CType' => 'textmedia',
                    'header' => 'Integration Test',
                ],
            ],
        ];

        $dataHandler->start($datamap, [], $backendUser);
        $dataHandler->process_datamap();

        $newUid = $dataHandler->substNEWwithIDs['NEW123'] ?? null;
        self::assertIsInt($newUid);

        $dataHandlerRecord = BackendUtility::getRecord('tt_content', $newUid);

        // Verify type-specific defaults were applied
        self::assertEquals('3', $dataHandlerRecord['header_layout']);
        self::assertEquals('ruler-before', $dataHandlerRecord['frame_class']);
    }

    #[Test]
    public function dataHandlerHandlesFallbacksConsistently(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Set up configuration with type-specific default for textmedia only
        $this->actionService->modifyRecord('pages', 88, [
            'TSconfig' => chr(10) .
                'TCAdefaults.tt_content.header_layout = 1' . chr(10) .
                'TCAdefaults.tt_content.header_layout.types.textmedia = 3',
        ]);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        $datamap = [
            'tt_content' => [
                'NEW456' => [
                    'pid' => 88,
                    'CType' => 'text',
                    'header' => 'Fallback Test',
                ],
            ],
        ];

        $dataHandler->start($datamap, [], $backendUser);
        $dataHandler->process_datamap();

        $newUid = $dataHandler->substNEWwithIDs['NEW456'] ?? null;
        $dataHandlerRecord = BackendUtility::getRecord('tt_content', $newUid);

        // Should fall back to field-level default for 'text' content type
        self::assertEquals('1', $dataHandlerRecord['header_layout']);
    }

    #[Test]
    public function dataHandlerWorksWithComplexInheritanceChain(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Set up page TSconfig with complex defaults
        $this->actionService->modifyRecord('pages', 88, [
            'TSconfig' => chr(10) .
                'TCAdefaults.tt_content.header_layout = 2' . chr(10) .
                'TCAdefaults.tt_content.header_layout.types.textmedia = 4' . chr(10) .
                'TCAdefaults.tt_content.frame_class = user-default' . chr(10) .
                'TCAdefaults.tt_content.frame_class.types.textmedia = user-type-default',
        ]);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        $datamap = [
            'tt_content' => [
                'NEW789' => [
                    'pid' => 88,
                    'CType' => 'textmedia',
                    'header' => 'Complex Inheritance Test',
                ],
            ],
        ];

        $dataHandler->start($datamap, [], $backendUser);
        $dataHandler->process_datamap();

        $newUid = $dataHandler->substNEWwithIDs['NEW789'] ?? null;
        $record = BackendUtility::getRecord('tt_content', $newUid);

        // Page TSconfig type-specific should be applied
        self::assertEquals('4', $record['header_layout']);
        self::assertEquals('user-type-default', $record['frame_class']);
    }

    #[Test]
    public function dataHandlerPreservesTypeSpecificDefaultsInComplexScenarios(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Set up page TSconfig with multiple type-specific defaults
        $this->actionService->modifyRecord('pages', 88, [
            'TSconfig' => chr(10) .
                'TCAdefaults.tt_content.header_layout.types.textmedia = 3' . chr(10) .
                'TCAdefaults.tt_content.header_layout.types.image = 2' . chr(10) .
                'TCAdefaults.tt_content.frame_class.types.textmedia = ruler-before',
        ]);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        // Create multiple records with different types
        $datamap = [
            'tt_content' => [
                'NEW_TEXTMEDIA' => [
                    'pid' => 88,
                    'CType' => 'textmedia',
                    'header' => 'Textmedia Element',
                ],
                'NEW_IMAGE' => [
                    'pid' => 88,
                    'CType' => 'image',
                    'header' => 'Image Element',
                ],
                'NEW_TEXT' => [
                    'pid' => 88,
                    'CType' => 'text',
                    'header' => 'Text Element',
                ],
            ],
        ];

        $dataHandler->start($datamap, [], $backendUser);
        $dataHandler->process_datamap();

        // Verify each record got the correct type-specific defaults
        $textmediaUid = $dataHandler->substNEWwithIDs['NEW_TEXTMEDIA'];
        $imageUid = $dataHandler->substNEWwithIDs['NEW_IMAGE'];
        $textUid = $dataHandler->substNEWwithIDs['NEW_TEXT'];

        $textmediaRecord = BackendUtility::getRecord('tt_content', $textmediaUid);
        $imageRecord = BackendUtility::getRecord('tt_content', $imageUid);
        $textRecord = BackendUtility::getRecord('tt_content', $textUid);

        // Textmedia should get type-specific defaults
        self::assertEquals('3', $textmediaRecord['header_layout']);
        self::assertEquals('ruler-before', $textmediaRecord['frame_class']);

        // Image should get type-specific header_layout, but no frame_class override
        self::assertEquals('2', $imageRecord['header_layout']);
        self::assertEquals('default', $imageRecord['frame_class']);

        // Text should not get any type-specific defaults
        self::assertEquals(0, $textRecord['header_layout']);
        self::assertEquals('default', $textRecord['frame_class']);
    }

    #[Test]
    public function dataHandlerHandlesMissingRecordType(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Set up page TSconfig with type-specific defaults
        $this->actionService->modifyRecord('pages', 88, [
            'TSconfig' => chr(10) .
                'TCAdefaults.tt_content.header_layout = 1' . chr(10) .
                'TCAdefaults.tt_content.header_layout.types.textmedia = 3',
        ]);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        // Create record without CType field - should fall back to field-level defaults
        $datamap = [
            'tt_content' => [
                'NEW_NO_TYPE' => [
                    'pid' => 88,
                    'header' => 'No Type Element',
                ],
            ],
        ];

        $dataHandler->start($datamap, [], $backendUser);
        $dataHandler->process_datamap();

        $newUid = $dataHandler->substNEWwithIDs['NEW_NO_TYPE'] ?? null;
        $record = BackendUtility::getRecord('tt_content', $newUid);

        // Should fall back to field-level default when no record type is provided
        self::assertEquals('1', $record['header_layout']);
    }

    #[Test]
    public function dataHandlerHandlesInvalidRecordType(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Set up page TSconfig with type-specific defaults
        $this->actionService->modifyRecord('pages', 88, [
            'TSconfig' => chr(10) .
                'TCAdefaults.tt_content.header_layout = 1' . chr(10) .
                'TCAdefaults.tt_content.header_layout.types.textmedia = 3',
        ]);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        // Create record with invalid CType - should fall back to field-level defaults
        $datamap = [
            'tt_content' => [
                'NEW_INVALID_TYPE' => [
                    'pid' => 88,
                    'CType' => 'nonexistent_type',
                    'header' => 'Invalid Type Element',
                ],
            ],
        ];

        $dataHandler->start($datamap, [], $backendUser);
        $dataHandler->process_datamap();

        $newUid = $dataHandler->substNEWwithIDs['NEW_INVALID_TYPE'] ?? null;
        $record = BackendUtility::getRecord('tt_content', $newUid);

        // Should fall back to field-level default when record type is invalid
        self::assertEquals('1', $record['header_layout']);
    }

    #[Test]
    public function dataHandlerHandlesTableWithoutTypeDefinition(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Set up page TSconfig with type-specific defaults for pages table (which has no type field)
        $this->actionService->modifyRecord('pages', 88, [
            'TSconfig' => chr(10) .
                'TCAdefaults.pages.title = Default Title' . chr(10) .
                'TCAdefaults.pages.title.types.sometype = Type Specific Title',
        ]);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        // Create page record - should ignore type-specific config and use field-level defaults
        $datamap = [
            'pages' => [
                'NEW_PAGE' => [
                    'pid' => 88,
                    'subtitle' => 'Test Page',
                ],
            ],
        ];

        $dataHandler->start($datamap, [], $backendUser);
        $dataHandler->process_datamap();

        $newUid = $dataHandler->substNEWwithIDs['NEW_PAGE'] ?? null;
        $record = BackendUtility::getRecord('pages', $newUid);

        // Should use field-level default since pages table has no type field
        self::assertEquals('Default Title', $record['title']);
    }

    #[Test]
    public function dataHandlerHandlesEmptyTypeSpecificConfiguration(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Set up page TSconfig with empty type-specific configuration
        $this->actionService->modifyRecord('pages', 88, [
            'TSconfig' => chr(10) .
                'TCAdefaults.tt_content.header_layout = 1' . chr(10) .
                'TCAdefaults.tt_content.header_layout.types.',
        ]);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        // Create record with textmedia type
        $datamap = [
            'tt_content' => [
                'NEW_EMPTY_TYPES' => [
                    'pid' => 88,
                    'CType' => 'textmedia',
                    'header' => 'Empty Types Config',
                ],
            ],
        ];

        $dataHandler->start($datamap, [], $backendUser);
        $dataHandler->process_datamap();

        $newUid = $dataHandler->substNEWwithIDs['NEW_EMPTY_TYPES'] ?? null;
        $record = BackendUtility::getRecord('tt_content', $newUid);

        // Should fall back to field-level default when types configuration is empty
        self::assertEquals('1', $record['header_layout']);
    }
}
