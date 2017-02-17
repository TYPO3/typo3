<?php

namespace TYPO3\CMS\Core\Tests\Unit\Type\File;

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

use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class ImageInfoTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{

    /**
     * @test
     */
    public function classImageInfoCanBeInstantiated()
    {
        $className = \TYPO3\CMS\Core\Type\File\ImageInfo::class;
        $classInstance = new \TYPO3\CMS\Core\Type\File\ImageInfo('FooFileName');
        $this->assertInstanceOf($className, $classInstance);
    }

    /**
     * @test
     */
    public function doesNotBreakOnImageInfoWithInvalidSvg()
    {
        $root = vfsStream::setup('root');
        $testFile = 'test.svg';
        vfsStream::newFile($testFile)->at($root)->setContent('Invalid XML.');

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'] = [
            'svg' => 'image/svg+xml',
            'youtube' => 'video/youtube',
            'vimeo' => 'video/vimeo',
        ];

        $graphicalFunctionsProphecy = $this->prophesize(GraphicalFunctions::class);
        $graphicalFunctionsProphecy->init()->shouldBeCalled();
        $graphicalFunctionsProphecy->imageMagickIdentify($root->url() . '/' . $testFile)->willReturn(null);
        GeneralUtility::addInstance(GraphicalFunctions::class, $graphicalFunctionsProphecy->reveal());

        $imageInfo = new ImageInfo($root->url() . '/' . $testFile);

        $this->assertEquals(0, $imageInfo->getWidth());
        $this->assertEquals(0, $imageInfo->getHeight());
    }
}
