<?php
namespace TYPO3\CMS\Core\Tests\Unit\LinkHandling;

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

use TYPO3\CMS\Core\LinkHandling\FileLinkHandler;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\MathUtility;

class FileLinkHandlerTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{

    /**
     * testing folders
     */

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
            'file with FAL uid - cool style' => [
                [
                    'uid' => 23
                ],
                [
                    'file' => 23
                ],
                't3://file?uid=23'
            ],
        ];
    }

    /**
     * Helpful to know in which if() clause the stuff gets in
     *
     * @test
     *
     * @param string $input
     * @param array  $expected
     * @param string $finalString
     *
     * @dataProvider resolveParametersForFilesDataProvider
     */
    public function resolveFileReferencesToSplitParameters($input, $expected, $finalString)
    {
        /** @var ResourceStorage|\PHPUnit_Framework_MockObject_MockObject $storageMock */
        $storage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var ResourceFactory|\PHPUnit_Framework_MockObject_MockObject $storageMock */
        $factory = $this->getMockBuilder(ResourceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        // fake methods to return proper objects
        $fileObject = new File(['identifier' => $expected['file']], $storage);
        $factory->expects($this->any())->method('getFileObject')->with($expected['file'])->willReturn($fileObject);
        $expected['file'] = $fileObject;

        /** @var FileLinkHandler|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(FileLinkHandler::class, ['dummy']);
        $subject->_set('resourceFactory', $factory);
        $this->assertEquals($expected, $subject->resolveHandlerData($input));
    }

    /**
     * Helpful to know in which if() clause the stuff gets in
     *
     * @test
     *
     * @param string $input
     * @param array  $parameters
     * @param string $expected
     *
     * @dataProvider resolveParametersForFilesDataProvider
     */
    public function splitParametersToUnifiedIdentifierForFiles($input, $parameters, $expected)
    {
        $fileObject = $this->getMockBuilder(File::class)
            ->setMethods(['getUid', 'getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();

        $uid = 0;
        if (MathUtility::canBeInterpretedAsInteger($parameters['file'])) {
            $uid = $parameters['file'];
        }
        $fileObject->expects($this->once())->method('getUid')->willReturn($uid);
        $fileObject->expects($this->any())->method('getIdentifier')->willReturn($parameters['file']);
        $parameters['file'] = $fileObject;

        $subject = new FileLinkHandler();
        $this->assertEquals($expected, $subject->asString($parameters));
    }
}
