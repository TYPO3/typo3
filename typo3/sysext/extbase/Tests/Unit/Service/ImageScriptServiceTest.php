<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Service;

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
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;
use TYPO3\CMS\Extbase\Service\ImageService;

/**
 * Test case
 */
class ImageScriptServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var ImageService
     */
    protected $subject;

    /**
     * @var EnvironmentService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $environmentService;

    /**
     * Initialize ImageService and environment service mock
     */
    protected function setUp()
    {
        $this->subject = new ImageService();
        $this->environmentService = $this->getMock(EnvironmentService::class);
        $this->inject($this->subject, 'environmentService', $this->environmentService);
        GeneralUtility::flushInternalRuntimeCaches();
        $_SERVER['HTTP_HOST'] = 'foo.bar';
    }

    /**
     * @test
     */
    public function fileIsUnwrappedFromReferenceForProcessing()
    {
        $reference = $this->getAccessibleMock(FileReference::class, [], [], '', false);
        $file = $this->getMock(File::class, [], [], '', false);
        $file->expects($this->once())->method('process')->willReturn($this->getMock(ProcessedFile::class, [], [], '', false));
        $reference->expects($this->once())->method('getOriginalFile')->willReturn($file);
        $reference->_set('file', $file);

        $this->subject->applyProcessingInstructions($reference, []);
    }

    /**
     * @return array
     */
    public function prefixIsCorrectlyAppliedToGetImageUriDataProvider()
    {
        return [
            'with scheme' => ['http://foo.bar/img.jpg', 'http://foo.bar/img.jpg'],
            'scheme relative' => ['//foo.bar/img.jpg', '//foo.bar/img.jpg'],
            'without scheme' => ['foo.bar/img.jpg', '/prefix/foo.bar/img.jpg'],
        ];
    }

    /**
     * @test
     * @dataProvider prefixIsCorrectlyAppliedToGetImageUriDataProvider
     */
    public function prefixIsCorrectlyAppliedToGetImageUri($imageUri, $expected)
    {
        $this->environmentService->expects($this->any())->method('isEnvironmentInFrontendMode')->willReturn(true);
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->absRefPrefix = '/prefix/';

        $file = $this->getMock(File::class, [], [], '', false);
        $file->expects($this->once())->method('getPublicUrl')->willReturn($imageUri);

        $this->assertSame($expected, $this->subject->getImageUri($file));
    }

    /**
     * @return array
     */
    public function prefixIsCorrectlyAppliedToGetImageUriWithAbsolutePathDataProvider()
    {
        return [
            'with scheme' => ['http://foo.bar/img.jpg', 'http://foo.bar/img.jpg'],
            'scheme relative' => ['//foo.bar/img.jpg', 'http://foo.bar/img.jpg'],
            'without scheme' => ['foo.bar/img.jpg', 'http://foo.bar/prefix/foo.bar/img.jpg'],
        ];
    }

    /**
     * @test
     * @dataProvider prefixIsCorrectlyAppliedToGetImageUriWithAbsolutePathDataProvider
     */
    public function prefixIsCorrectlyAppliedToGetImageUriWithForcedAbsoluteUrl($imageUri, $expected)
    {
        $this->environmentService->expects($this->any())->method('isEnvironmentInFrontendMode')->willReturn(true);
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->absRefPrefix = '/prefix/';

        $file = $this->getMock(File::class, [], [], '', false);
        $file->expects($this->once())->method('getPublicUrl')->willReturn($imageUri);

        $this->assertSame($expected, $this->subject->getImageUri($file, true));
    }
}
