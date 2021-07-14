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

use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

class ImageViewHelperTest extends FunctionalTestCase
{
    public function invalidArgumentsDataProvider(): array
    {
        return [
            [['image' => null], 1382284106],
            [['src' => null], 1382284106],
            [['src' => ''], 1382284106],
            [['src' => 'something', 'image' => 'something'], 1382284106],
            [['src' => 'something', 'fileExtension' => 'dummy'], 1618989190],
        ];
    }

    /**
     * @test
     * @dataProvider invalidArgumentsDataProvider
     */
    public function renderThrowsExceptionOnInvalidArguments(array $arguments, int $expectedExceptionCode): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode($expectedExceptionCode);

        $viewHelper = new ImageViewHelper();
        $viewHelper->setArguments($arguments);
        $viewHelper->render();
    }

    public function renderReturnsExpectedMarkupDataProvider(): array
    {
        return [
            'crop false' => [
                '<f:image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/ImageViewHelperTest.jpg" width="300" height="500" crop="false" />',
                '@^<img src="vendor/phpunit/phpunit/typo3temp/assets/_processed_/b/3/csm_ImageViewHelperTest_.*\.jpg" width="300" height="500" alt="" />$@'
            ],
            'crop null' => [
                '<f:image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/ImageViewHelperTest.jpg" width="300" height="500" crop="null" />',
                '@^<img src="vendor/phpunit/phpunit/typo3temp/assets/_processed_/b/3/csm_ImageViewHelperTest_.*\.jpg" width="300" height="500" alt="" />$@'
            ],
            'jpg file extension' => [
                '<f:image src="EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/ImageViewHelperTest.jpg" width="300" height="500" crop="null" fileExtension="jpg" />',
                '@^<img src="vendor/phpunit/phpunit/typo3temp/assets/_processed_/b/3/csm_ImageViewHelperTest_.*\.jpg" width="300" height="500" alt="" />$@'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderReturnsExpectedMarkupDataProvider
     */
    public function renderReturnsExpectedMarkup(string $template, string $expected): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource($template);
        self::assertRegExp($expected, $view->render());
    }
}
