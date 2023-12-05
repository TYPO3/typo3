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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests various places to set default values properly for new records
 */
final class DefaultValuesTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_defaulttsconfig',
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DA' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'da_DK.UTF8'],
    ];

    private ActionService $actionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->setUpBackendUser(1);
        $this->actionService = new ActionService();
        Bootstrap::initializeLanguageObject();
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
            ]
        );
    }

    /**
     * @test
     */
    public function defaultValuesFromTCAForNewRecordsIsRespected(): void
    {
        $GLOBALS['TCA']['pages']['columns']['subtitle']['config']['default'] = 'tca default subtitle';
        $GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['default'] = 'tca default bodytext';

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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function defaultValueForNullTextfieldsIsConsidered(): void
    {
        // New content element without bodytext
        $GLOBALS['TCA']['tt_content']['columns']['bodytext']['l10n_mode'] = 'exclude';
        $GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['enableRichtext'] = true;
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
}
