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

namespace TYPO3\CMS\Extbase\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Verifies that the "Startingpoint" (tt_content.pages / tt_content.recursive)
 * of an Extbase plugin content element overrides the configured
 * plugin.tx_<signature>.persistence.storagePid, independent of whether the
 * page is rendered via FLUIDTEMPLATE (classic CONTENT chain) or PAGEVIEW
 * (Record based rendering).
 */
final class PluginStartingpointStoragePidTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en-US'],
    ];

    protected array $coreExtensionsToLoad = ['fluid_styled_content'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/test_startingpoint',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Startingpoint/PagesAndContent.csv');
        $this->writeSiteConfiguration(
            'startingpoint',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ],
        );
    }

    public static function expectedPersistenceStoragePidAndRecordsAreResolvedDataProvider(): \Generator
    {
        // Persistence StoragePageId using `tt_content.pages` (StartingPoint override)
        yield 'FLUIDTEMPLATE, recursive disabled' => [
            'typoScriptFile' => 'fluidtemplate.typoscript',
            'pageId' => 2,
            'expectedRecords' => ['storagePidProbe:[20]', 'storagePidProbe:querysettings[20]', 'tt_content:301'],
            'notExpectedRecords' => ['storagePidProbe:[30]', 'storagePidProbe:querysettings[30]', 'tt_content:302', 'tt_content:303', 'tt_content:100'],
        ];
        yield 'FLUIDTEMPLATE, recursive enabled' => [
            'typoScriptFile' => 'fluidtemplate.typoscript',
            'pageId' => 4,
            'expectedRecords' => ['storagePidProbe:[20,21]', 'storagePidProbe:querysettings[20,21]', 'tt_content:301', 'tt_content:302'],
            'notExpectedRecords' => ['storagePidProbe:[30]', 'storagePidProbe:querysettings[30]', 'tt_content:303', 'tt_content:102'],
        ];
        yield 'PAGEVIEW, recursive disabled' => [
            'typoScriptFile' => 'pageview.typoscript',
            'pageId' => 3,
            'expectedRecords' => ['storagePidProbe:[20]', 'storagePidProbe:querysettings[20]', 'tt_content:301'],
            'notExpectedRecords' => ['storagePidProbe:[30]', 'storagePidProbe:querysettings[30]', 'tt_content:302', 'tt_content:303', 'tt_content:101'],
        ];
        yield 'PAGEVIEW, recursive enabled' => [
            'typoScriptFile' => 'pageview.typoscript',
            'pageId' => 5,
            'expectedRecords' => ['storagePidProbe:[20,21]', 'storagePidProbe:querysettings[20,21]', 'tt_content:301', 'tt_content:302'],
            'notExpectedRecords' => ['storagePidProbe:[30]', 'storagePidProbe:querysettings[30]', 'tt_content:303', 'tt_content:103'],
        ];
        yield 'PAGEVIEW f:cObject, recursive disabled' => [
            'typoScriptFile' => 'pageview.typoscript',
            'pageId' => 6,
            'expectedRecords' => ['storagePidProbe:[20]', 'storagePidProbe:querysettings[20]', 'tt_content:301'],
            'notExpectedRecords' => ['storagePidProbe:[30]', 'storagePidProbe:querysettings[30]', 'tt_content:302', 'tt_content:303', 'tt_content:104'],
        ];
        yield 'PAGEVIEW f:cObject, recursive enabled' => [
            'typoScriptFile' => 'pageview.typoscript',
            'pageId' => 7,
            'expectedRecords' => ['storagePidProbe:[20,21]', 'storagePidProbe:querysettings[20,21]', 'tt_content:301', 'tt_content:302'],
            'notExpectedRecords' => ['storagePidProbe:[30]', 'storagePidProbe:querysettings[30]', 'tt_content:303', 'tt_content:105'],
        ];
        // Persistence StoragePageId from extension plugin settings
        yield 'FLUIDTEMPLATE, recursive disabled, without override' => [
            'typoScriptFile' => 'fluidtemplate.typoscript',
            'pageId' => 12,
            'expectedRecords' => ['storagePidProbe:[30]', 'storagePidProbe:querysettings[30]', 'tt_content:303'],
            'notExpectedRecords' => ['storagePidProbe:[20]', 'storagePidProbe:querysettings[20]', 'tt_content:302'],
        ];
        yield 'FLUIDTEMPLATE, recursive enabled, without override' => [
            'typoScriptFile' => 'fluidtemplate.typoscript',
            'pageId' => 14,
            'expectedRecords' => ['storagePidProbe:[30]', 'storagePidProbe:querysettings[30]', 'tt_content:303'],
            'notExpectedRecords' => ['storagePidProbe:[20,21]', 'storagePidProbe:querysettings[20,21]', 'tt_content:301', 'tt_content:302'],
        ];
        yield 'PAGEVIEW, recursive disabled, without override' => [
            'typoScriptFile' => 'pageview.typoscript',
            'pageId' => 13,
            'expectedRecords' => ['storagePidProbe:[30]', 'storagePidProbe:querysettings[30]', 'tt_content:303'],
            'notExpectedRecords' => ['storagePidProbe:[20]', 'storagePidProbe:querysettings[20]', 'tt_content:302', 'tt_content:301'],
        ];
        yield 'PAGEVIEW, recursive enabled, without override' => [
            'typoScriptFile' => 'pageview.typoscript',
            'pageId' => 15,
            'expectedRecords' => ['storagePidProbe:[30]', 'storagePidProbe:querysettings[30]', 'tt_content:303'],
            'notExpectedRecords' => ['storagePidProbe:[20,21]', 'storagePidProbe:querysettings[20,21]', 'tt_content:301'],
        ];
        yield 'PAGEVIEW f:cObject, recursive disabled, without override' => [
            'typoScriptFile' => 'pageview.typoscript',
            'pageId' => 16,
            'expectedRecords' => ['storagePidProbe:[30]', 'storagePidProbe:querysettings[30]', 'tt_content:303'],
            'notExpectedRecords' => ['storagePidProbe:[20]', 'storagePidProbe:querysettings[20]', 'tt_content:302', 'tt_content:301'],
        ];
        yield 'PAGEVIEW f:cObject, recursive enabled, without override' => [
            'typoScriptFile' => 'pageview.typoscript',
            'pageId' => 17,
            'expectedRecords' => ['storagePidProbe:[30]', 'storagePidProbe:querysettings[30]', 'tt_content:303'],
            'notExpectedRecords' => ['storagePidProbe:[20,21]', 'storagePidProbe:querysettings[20,21]', 'tt_content:301', 'tt_content:302'],
        ];
    }

    #[DataProvider('expectedPersistenceStoragePidAndRecordsAreResolvedDataProvider')]
    #[Test]
    public function expectedPersistenceStoragePidAndRecordsAreResolved(string $typoScriptFile, int $pageId, array $expectedRecords, array $notExpectedRecords): void
    {
        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                'EXT:extbase/Tests/Functional/Fixtures/Extensions/blog_example/Configuration/TypoScript/setup.typoscript',
                'EXT:test_startingpoint/Configuration/TypoScript/' . $typoScriptFile,
            ],
        );
        $body = (string)$this->executeFrontendSubRequest((new InternalRequest())->withPageId($pageId))->getBody();
        foreach ($expectedRecords as $expectedRecord) {
            self::assertStringContainsString($expectedRecord, $body);
        }
        foreach ($notExpectedRecords as $notExpectedRecord) {
            self::assertStringNotContainsString($notExpectedRecord, $body);
        }
    }
}
