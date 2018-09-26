<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Tests\Unit\Resource\OnlineMedia\Helpers;

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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\VimeoHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class VimeoHelperTest
 */
class VimeoHelperTest extends UnitTestCase
{
    /**
     * @var VimeoHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * @var string
     */
    protected $extension;

    /**
     * Set up the test
     */
    protected function setUp()
    {
        parent::setUp();

        $this->extension = 'video/vimeo';
        $this->subject = $this->getAccessibleMock(VimeoHelper::class, ['transformMediaIdToFile'], [$this->extension]);
    }

    /**
     * @test
     * @dataProvider transformUrlDataProvider
     *
     * @param string $url
     * @param string $videoId
     * @param File|null $expectedResult
     *
     * @throws \ReflectionException
     */
    public function transformUrlToFileReturnsExpectedResult($url, $videoId, $expectedResult)
    {
        /** @var Folder|\PHPUnit_Framework_MockObject_MockObject $mockedFolder */
        $mockedFolder = $this->createMock(Folder::class);

        $this->subject->expects($this->any())->method('transformMediaIdToFile')
            ->with($videoId, $mockedFolder, $this->extension)
            ->will($this->returnValue($expectedResult));

        $result = $this->subject->transformUrlToFile($url . $videoId, $mockedFolder);

        $this->assertSame($expectedResult, $result);
    }

    public function transformUrlDataProvider()
    {
        $fileResourceMock = $this->createMock(File::class);

        return [
            [null, null, null],
            ['https://typo3.org/', null, null],
            ['https://vimeo.com/', '7215347324', $fileResourceMock],
            ['https://vimeo.com/', '7215347324/hasf8a65sdsa7d', $fileResourceMock],
            ['https://player.vimeo.com/', '7215347324', $fileResourceMock],
            ['https://player.vimeo.com/', '7215347324/hasf8a65sdsa7d', $fileResourceMock]
        ];
    }
}
