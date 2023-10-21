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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers;

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ImageViewHelperTest extends FunctionalTestCase
{
    protected array $additionalFoldersToCreate = [
        '/fileadmin/user_upload',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/fluid/Tests/Functional/Fixtures/ViewHelpers/Link/FileViewHelper/Folders/fileadmin/user_upload/typo3_image2.jpg' => 'fileadmin/user_upload/typo3_image2.jpg',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ViewHelpers/Link/FileViewHelper/DatabaseImport.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
    }

    public static function invalidArgumentsDataProvider(): array
    {
        return [
            ['<f:image />', 1382284106],
            ['<f:image src="" />', 1382284106],
            ['<f:image src="something" />', 1509741911],
            ['<f:image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/" />', 1509741914],
            ['<f:image src="fileadmin/user_upload/" />', 1509741912],
            ['<f:image src="something" fileExtension="dummy" />', 1618989190],
        ];
    }

    /**
     * @test
     * @dataProvider invalidArgumentsDataProvider
     */
    public function renderThrowsExceptionOnInvalidArguments(string $template, int $expectedExceptionCode): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode($expectedExceptionCode);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        (new TemplateView($context))->render();
    }

    public static function renderReturnsExpectedMarkupDataProvider(): array
    {
        return [
            'crop false' => [
                '<f:image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/ImageViewHelperTest.jpg" width="300" height="500" crop="false" />',
                '@^<img src="typo3temp/assets/_processed_/b/3/csm_ImageViewHelperTest_.*\.jpg" width="300" height="500" alt="" />$@',
            ],
            'crop null' => [
                '<f:image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/ImageViewHelperTest.jpg" width="300" height="500" crop="null" />',
                '@^<img src="typo3temp/assets/_processed_/b/3/csm_ImageViewHelperTest_.*\.jpg" width="300" height="500" alt="" />$@',
            ],
            'crop as array' => [
                '<f:image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/ImageViewHelperTest.jpg" width="300" height="500" crop="{\'x\': 200, \'y\': 200, \'width\': 200, \'height\': 200}" />',
                '@^<img src="typo3temp/assets/_processed_/b/3/csm_ImageViewHelperTest_.*\.jpg" width="300" height="500" alt="" />$@',
            ],
            'jpg file extension' => [
                '<f:image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/ImageViewHelperTest.jpg" width="300" height="500" crop="null" fileExtension="jpg" />',
                '@^<img src="typo3temp/assets/_processed_/b/3/csm_ImageViewHelperTest_.*\.jpg" width="300" height="500" alt="" />$@',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderReturnsExpectedMarkupDataProvider
     */
    public function renderReturnsExpectedMarkup(string $template, string $expected): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertMatchesRegularExpression($expected, (new TemplateView($context))->render());
    }
}
