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

namespace TYPO3\ThemeCamino\Tests\Functional\Rendering;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TextpicRenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['theme_camino'];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/frontend/Tests/Functional/Fixtures/ViewHelperImages/ImageViewHelperTest1.svg' => 'fileadmin/user_upload/FALImageViewHelperTest1.svg',
        'typo3/sysext/frontend/Tests/Functional/Fixtures/ViewHelperImages/ImageViewHelperTest2.svg' => 'fileadmin/user_upload/FALImageViewHelperTest2.svg',
    ];

    protected array $additionalFoldersToCreate = [
        '/fileadmin/user_upload',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCsvDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCsvDataSet(__DIR__ . '/../Fixtures/TextpicRendering.csv');
        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:theme_camino/Configuration/Sets/camino/setup.typoscript',
                'EXT:theme_camino/Tests/Functional/Fixtures/TypoScript/TextpicRendering.typoscript',
            ]
        );
        $this->writeSiteConfiguration(
            'camino',
            $this->buildSiteConfiguration(1, 'https://camino.example/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
    }

    #[Test]
    public function allImagesOfTextpicElementAreRendered(): void
    {
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://camino.example/')->withPageId(1)
        );

        $content = (string)$response->getBody();

        self::assertSame(2, substr_count($content, '<figure class="figure">'));
        self::assertMatchesRegularExpression('#<img[^>]*src="[^"]*FALImageViewHelperTest1[^"]*"#', $content);
        self::assertMatchesRegularExpression('#<img[^>]*src="[^"]*FALImageViewHelperTest2[^"]*"#', $content);
    }
}
