<?php

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

namespace TYPO3\CMS\Core\Tests\Unit\Type\File;

use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ImageInfoTest extends UnitTestCase
{

    /**
     * @test
     */
    public function classImageInfoCanBeInstantiated()
    {
        $className = ImageInfo::class;
        $classInstance = new ImageInfo('FooFileName');
        self::assertInstanceOf($className, $classInstance);
    }

    /**
     * @return array
     */
    public function doesNotBreakOnImageInfoWithInvalidSvgDataProvider(): array
    {
        return [
            ['Invalid XML.', 0, 0],
            [
                '<?xml version="1.0" encoding="utf-8"?>
                <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd" [
                    <!ENTITY ns_a "http://ns.typo3.com/test/a/1.0/">
                    <!ENTITY ns_b "http://ns.typo3.com/test/b/1.0/">
                ]>
                <svg version="1.0"
                    xmlns:x="&ns_a;"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink"
                    xml:space="preserve"
                    x="0px" y="0px" viewBox="0 0 436 177">
                    <metadata>
                        <sfw xmlns="&ns_b;">
                            <slices></slices>
                        </sfw>
                    </metadata>
                </svg>',
                436,
                177
            ],
        ];
    }

    /**
     * @test
     * @dataProvider doesNotBreakOnImageInfoWithInvalidSvgDataProvider
     * @param string $svg
     * @param int $width
     * @param int $height
     */
    public function doesNotBreakOnImageInfoWithInvalidSvg($svg, $width, $height)
    {
        $this->resetSingletonInstances = true;

        $root = vfsStream::setup('root');
        $testFile = 'test.svg';
        vfsStream::newFile($testFile)->at($root)->setContent($svg);

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'] = [
            'svg' => 'image/svg+xml',
            'youtube' => 'video/youtube',
            'vimeo' => 'video/vimeo',
        ];

        $graphicalFunctionsProphecy = $this->prophesize(GraphicalFunctions::class);
        $graphicalFunctionsProphecy->imageMagickIdentify($root->url() . '/' . $testFile)->willReturn(null);
        GeneralUtility::addInstance(GraphicalFunctions::class, $graphicalFunctionsProphecy->reveal());

        $loggerProphecy = $this->prophesize(Logger::class);

        $imageInfo = new ImageInfo($root->url() . '/' . $testFile);
        $imageInfo->setLogger($loggerProphecy->reveal());

        self::assertSame($width, $imageInfo->getWidth());
        self::assertSame($height, $imageInfo->getHeight());

        GeneralUtility::makeInstance(GraphicalFunctions::class);
    }

    /**
     * @return array
     */
    public function canDetectImageSizesDataProvider(): array
    {
        return [
            'svg' => ['test.svg', 80, 80],
            'jpg' => ['test.jpg', 600, 388],
            'png' => ['test.png', 600, 388],
        ];
    }

    /**
     * @test
     * @dataProvider canDetectImageSizesDataProvider
     * @param string $file
     * @param int $width
     * @param int $height
     */
    public function canDetectImageSizes($file, $width, $height)
    {
        $logger = $this->prophesize(Logger::class)->reveal();
        $imageInfo = new ImageInfo(__DIR__ . '/../Fixture/' . $file);
        $imageInfo->setLogger($logger);

        self::assertSame($width, $imageInfo->getWidth());
        self::assertSame($height, $imageInfo->getHeight());
    }
}
