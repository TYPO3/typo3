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

namespace TYPO3\ThemeCamino\Tests\Functional\BackendLayout;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\View\BackendLayout\PageTsBackendLayoutDataProvider;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BackendLayoutPreviewTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['theme_camino'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCsvDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'camino',
            array_replace_recursive(
                $this->buildSiteConfiguration(1, 'https://camino.example/'),
                [
                    'dependencies' => [
                        'typo3/theme-camino',
                    ],
                ]
            ),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
    }

    public static function caminoBackendLayoutsDataProvider(): array
    {
        return [
            'CaminoStartpage' => ['CaminoStartpage'],
            'CaminoContentpage' => ['CaminoContentpage'],
            'CaminoContentpageSidebar' => ['CaminoContentpageSidebar'],
        ];
    }

    #[DataProvider('caminoBackendLayoutsDataProvider')]
    #[Test]
    public function backendLayoutProvidesPreviewImage(string $identifier): void
    {
        $backendLayout = new PageTsBackendLayoutDataProvider()->getBackendLayout($identifier, 1);

        self::assertNotNull($backendLayout, 'Backend layout "' . $identifier . '" is not available');
        $iconPath = $backendLayout->getIconPath();
        self::assertNotSame('', $iconPath, 'Backend layout "' . $identifier . '" does not provide a preview image');
        self::assertFileExists(GeneralUtility::getFileAbsFileName($iconPath));
    }
}
