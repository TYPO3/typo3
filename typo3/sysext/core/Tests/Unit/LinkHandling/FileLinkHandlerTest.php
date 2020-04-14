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

namespace TYPO3\CMS\Core\Tests\Unit\LinkHandling;

use TYPO3\CMS\Core\LinkHandling\FileLinkHandler;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FileLinkHandlerTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSingletonInstances = true;
    }

    /**
     * Data provider for pointing to files
     * t3:file:1:myfolder/myidentifier.jpg
     * t3:folder:1:myfolder
     *
     * @return array
     */
    public function resolveParametersForFilesDataProvider()
    {
        return [
            'file without FAL - cool style' => [
                [
                    'identifier' => 'fileadmin/deep/down.jpg'
                ],
                [
                    'file' => 'fileadmin/deep/down.jpg'
                ],
                't3://file?identifier=fileadmin%2Fdeep%2Fdown.jpg'
            ],
            'file without FAL and anchor - cool style' => [
                [
                    'identifier' => 'fileadmin/deep/down.jpg',
                    'fragment' => 'page-13'
                ],
                [
                    'file' => 'fileadmin/deep/down.jpg',
                    'fragment' => 'page-13'
                ],
                't3://file?identifier=fileadmin%2Fdeep%2Fdown.jpg#page-13'
            ],
            'file with FAL uid - cool style' => [
                [
                    'uid' => 23
                ],
                [
                    'file' => 23
                ],
                't3://file?uid=23'
            ],
            'file with FAL uid and anchor - cool style' => [
                [
                    'uid' => 23,
                    'fragment' => 'page-13'
                ],
                [
                    'file' => 23,
                    'fragment' => 'page-13'
                ],
                't3://file?uid=23#page-13'
            ],
        ];
    }

    /**
     * Helpful to know in which if() clause the stuff gets in
     *
     * @test
     *
     * @param array $input
     * @param array  $expected
     *
     * @dataProvider resolveParametersForFilesDataProvider
     */
    public function resolveFileReferencesToSplitParameters(array $input, array $expected): void
    {
        /** @var ResourceStorage|\PHPUnit\Framework\MockObject\MockObject $storageMock */
        $storage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var ResourceFactory|\PHPUnit\Framework\MockObject\MockObject $storageMock */
        $factory = $this->getMockBuilder(ResourceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        // fake methods to return proper objects
        $fileObject = new File(['identifier' => $expected['file'], 'name' => 'foobar.txt'], $storage);
        $factory->method('getFileObject')->with($expected['file'])->willReturn($fileObject);
        $factory->method('getFileObjectFromCombinedIdentifier')->with($expected['file'])->willReturn($fileObject);
        $expected['file'] = $fileObject;
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $factory);

        $subject = new FileLinkHandler();

        self::assertEquals($expected, $subject->resolveHandlerData($input));
    }

    /**
     * Helpful to know in which if() clause the stuff gets in
     *
     * @test
     *
     * @param array $input
     * @param array  $parameters
     * @param string $expected
     *
     * @dataProvider resolveParametersForFilesDataProvider
     */
    public function splitParametersToUnifiedIdentifierForFiles(array $input, array $parameters, string $expected): void
    {
        $fileObject = $this->getMockBuilder(File::class)
            ->onlyMethods(['getUid', 'getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();

        $uid = 0;
        if (MathUtility::canBeInterpretedAsInteger($parameters['file'])) {
            $uid = $parameters['file'];
        }
        $fileObject->expects(self::once())->method('getUid')->willReturn($uid);
        $fileObject->method('getIdentifier')->willReturn($parameters['file']);
        $parameters['file'] = $fileObject;

        $subject = new FileLinkHandler();
        self::assertEquals($expected, $subject->asString($parameters));
    }
}
