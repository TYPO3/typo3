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
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests various places to set default values properly for new records
 */
final class DefaultValuesTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DA' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'da_DK.UTF8'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_defaulttsconfig',
    ];

    private ActionService $actionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->actionService = new ActionService();
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
            ]
        );
    }

    #[Test]
    public function defaultValuesFromTCAForNewRecordsIsRespected(): void
    {
        $GLOBALS['TCA']['pages']['columns']['subtitle']['config']['default'] = 'tca default subtitle';
        $GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['default'] = 'tca default bodytext';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        // Create page and verify default from TCA is applied
        $map = $this->actionService->createNewRecord('pages', 88, [
            'title' => 'A new age',
        ]);
        $newPageId = reset($map['pages']);
        $newPageRecord = BackendUtility::getRecord('pages', $newPageId);
        self::assertEquals('tca default subtitle', $newPageRecord['subtitle']);

        // Add content element and verify default from TCA is not applied when value is given
        $map = $this->actionService->createNewRecord('tt_content', $newPageId, [
            'bodytext' => '',
            'title' => 'foo',
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);
        self::assertEquals('', $newContentRecord['bodytext']);

        // Add another content element and verify default from TCA is applied
        $map = $this->actionService->createNewRecord('tt_content', $newPageId, [
            'title' => 'foo',
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);
        self::assertEquals('tca default bodytext', $newContentRecord['bodytext']);
    }

    #[Test]
    public function defaultValuesFromGlobalTSconfigForNewRecordsIsRespected(): void
    {
        // TCAdefaults from ext:test_defaulttsconfig/Configuration/page.tsconfig kick in here
        $map = $this->actionService->createNewRecord('pages', 88, [
            'title' => 'A new age',
        ]);
        $newPageId = reset($map['pages']);
        $newPageRecord = BackendUtility::getRecord('pages', $newPageId);
        self::assertEquals('from pagets, with love', $newPageRecord['keywords']);

        // Add content element and verify Page TSconfig TCAdefaults are not applied when value is given
        $map = $this->actionService->createNewRecord('tt_content', $newPageId, [
            'header' => '',
            'bodytext' => 'Random bodytext',
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);
        self::assertEquals('', $newContentRecord['header']);

        // Add content element and verify Page TSconfig TCAdefaults are applied
        $map = $this->actionService->createNewRecord('tt_content', $newPageId, [
            'bodytext' => 'Random bodytext',
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);
        self::assertEquals('global space', $newContentRecord['header']);
    }

    #[Test]
    public function defaultValuesFromPageSpecificTSconfigForNewRecordsIsRespected(): void
    {
        // TCAdefaults from ext:test_defaulttsconfig/Configuration/page.tsconfig kick in here,
        // but are overridden by specific page record TSconfig here.
        $this->actionService->modifyRecord('pages', 88, [
            'TSconfig' => chr(10) .
                'TCAdefaults.pages.keywords = I am specific, not generic' . chr(10) .
                'TCAdefaults.tt_content.header = local space',
        ]);

        // Add subpage and verify TSconfig from above page kicks in.
        $map = $this->actionService->createNewRecord('pages', 88, [
            'title' => 'A new age',
        ]);
        $newPageId = reset($map['pages']);
        $newPageRecord = BackendUtility::getRecord('pages', $newPageId);
        self::assertEquals('I am specific, not generic', $newPageRecord['keywords']);

        // Create content element with given header, Page TSconfig TCAdefaults does not kick in.
        $map = $this->actionService->createNewRecord('tt_content', $newPageId, [
            'header' => '',
            'bodytext' => 'Random bodytext',
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);
        self::assertEquals('', $newContentRecord['header']);

        // Create content element without given header, Page TSconfig TCAdefaults kicks in.
        $map = $this->actionService->createNewRecord('tt_content', $newPageId, [
            'bodytext' => 'Random bodytext',
        ]);
        $newContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $newContentId);
        self::assertEquals('local space', $newContentRecord['header']);
    }

    #[Test]
    public function defaultValueForNullTextfieldsIsConsidered(): void
    {
        // New content element without bodytext
        $GLOBALS['TCA']['tt_content']['columns']['bodytext']['l10n_mode'] = 'exclude';
        $GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['enableRichtext'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $map = $this->actionService->createNewRecord('tt_content', 88, [
            'header' => 'Random header',
            'bodytext' => null,
        ]);
        $newContentId = reset($map['tt_content']);
        $map = $this->actionService->localizeRecord('tt_content', $newContentId, 1);
        $translatedContentId = reset($map['tt_content']);
        $newContentRecord = BackendUtility::getRecord('tt_content', $translatedContentId);
        self::assertNull($newContentRecord['bodytext']);
    }

    #[Test]
    public function typeSpecificTcaDefaultsAreApplied(): void
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->setUpBackendUser(2));

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
        );

        // Create a text content element - should use field-level default
        $map = $this->actionService->createNewRecord('tt_content', 1, [
            'CType' => 'text',
            'header' => 'Test Text Element',
        ]);
        $newTextContentId = reset($map['tt_content']);
        $textContentRecord = BackendUtility::getRecord('tt_content', $newTextContentId);
        self::assertEquals('1', $textContentRecord['header_layout']);

        // Create a textmedia content element - should use type-specific default
        $map = $this->actionService->createNewRecord('tt_content', 1, [
            'CType' => 'textmedia',
            'header' => 'Test Textmedia Element',
        ]);
        $newTextmediaContentId = reset($map['tt_content']);
        $textmediaContentRecord = BackendUtility::getRecord('tt_content', $newTextmediaContentId);
        self::assertEquals('3', $textmediaContentRecord['header_layout']);
    }

    #[Test]
    public function typeSpecificTcaDefaultsWorkWithPageTsConfig(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
        );

        $this->actionService->modifyRecord('pages', 88, [
            'TSconfig' => chr(10) .
                'TCAdefaults.tt_content.header_layout = 1' . chr(10) .
                'TCAdefaults.tt_content.header_layout.types.textmedia = 4' . chr(10) .
                'TCAdefaults.tt_content.frame_class = default' . chr(10) .
                'TCAdefaults.tt_content.frame_class.types.textmedia = ruler-after',
        ]);

        // Create textmedia content element on page with TSconfig
        $map = $this->actionService->createNewRecord('tt_content', 88, [
            'CType' => 'textmedia',
            'header' => 'Test with Page TSconfig',
        ]);
        $newContentId = reset($map['tt_content']);
        $contentRecord = BackendUtility::getRecord('tt_content', $newContentId);

        self::assertEquals('4', $contentRecord['header_layout']);
        self::assertEquals('ruler-after', $contentRecord['frame_class']);
    }

    #[Test]
    public function typeSpecificTcaDefaultsWithMultipleFields(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
        );

        // Set up complex page TSconfig with multiple type-specific defaults
        $this->actionService->modifyRecord('pages', 88, [
            'TSconfig' => chr(10) .
                'TCAdefaults.tt_content.header_layout = 1' . chr(10) .
                'TCAdefaults.tt_content.header_layout.types.textmedia = 3' . chr(10) .
                'TCAdefaults.tt_content.header_layout.types.image = 2' . chr(10) .
                'TCAdefaults.tt_content.frame_class = default' . chr(10) .
                'TCAdefaults.tt_content.frame_class.types.textmedia = ruler-before' . chr(10) .
                'TCAdefaults.tt_content.space_before_class = none',
        ]);

        // Create textmedia element
        $map = $this->actionService->createNewRecord('tt_content', 88, [
            'CType' => 'textmedia',
            'header' => 'Test Textmedia',
        ]);
        $textmediaContentId = reset($map['tt_content']);
        $textmediaRecord = BackendUtility::getRecord('tt_content', $textmediaContentId);

        self::assertEquals('3', $textmediaRecord['header_layout']);
        self::assertEquals('ruler-before', $textmediaRecord['frame_class']);
        self::assertEquals('none', $textmediaRecord['space_before_class']);

        // Create image element
        $map = $this->actionService->createNewRecord('tt_content', 88, [
            'CType' => 'image',
            'header' => 'Test Image',
        ]);
        $imageContentId = reset($map['tt_content']);
        $imageRecord = BackendUtility::getRecord('tt_content', $imageContentId);

        self::assertEquals('2', $imageRecord['header_layout']);
        self::assertEquals('default', $imageRecord['frame_class']);
        self::assertEquals('none', $imageRecord['space_before_class']);

        // Create text element (no type-specific overrides)
        $map = $this->actionService->createNewRecord('tt_content', 88, [
            'CType' => 'text',
            'header' => 'Test Text',
        ]);
        $textContentId = reset($map['tt_content']);
        $textRecord = BackendUtility::getRecord('tt_content', $textContentId);

        self::assertEquals('1', $textRecord['header_layout']);
        self::assertEquals('default', $textRecord['frame_class']);
        self::assertEquals('none', $textRecord['space_before_class']);
    }

    #[Test]
    public function typeSpecificTcaDefaultsWithInheritance(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
        );

        // Set up page TSconfig with inheritance test (parent + child configuration)
        $this->actionService->modifyRecord('pages', 88, [
            'TSconfig' => chr(10) .
                'TCAdefaults.tt_content.header_layout = 0' . chr(10) .
                'TCAdefaults.tt_content.header_layout.types.textmedia = 1' . chr(10) .
                'TCAdefaults.tt_content.header_layout.types.textmedia = 5',
        ]);

        // Create textmedia element - later configuration should override earlier
        $map = $this->actionService->createNewRecord('tt_content', 88, [
            'CType' => 'textmedia',
            'header' => 'Test Override',
        ]);
        $contentId = reset($map['tt_content']);
        $record = BackendUtility::getRecord('tt_content', $contentId);

        self::assertEquals('5', $record['header_layout']);
    }

    #[Test]
    public function typeSpecificTcaDefaultsWithOnlyTypeSpecificConfiguration(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
        );

        // Only type-specific defaults, no field-level defaults - use page TSconfig
        $this->actionService->modifyRecord('pages', 88, [
            'TSconfig' => chr(10) .
                'TCAdefaults.tt_content.header_layout.types.textmedia = 7',
        ]);

        // Create textmedia element
        $map = $this->actionService->createNewRecord('tt_content', 88, [
            'CType' => 'textmedia',
            'header' => 'Test Only Type Specific',
        ]);
        $contentId = reset($map['tt_content']);
        $record = BackendUtility::getRecord('tt_content', $contentId);

        self::assertEquals('7', $record['header_layout']);

        // Create text element (should not get any default since no field-level or matching type-specific)
        $map = $this->actionService->createNewRecord('tt_content', 88, [
            'CType' => 'text',
            'header' => 'Test No Default',
        ]);
        $textContentId = reset($map['tt_content']);
        $textRecord = BackendUtility::getRecord('tt_content', $textContentId);

        // Should use TCA default
        self::assertSame($textRecord['header_layout'], 0);
    }
}
