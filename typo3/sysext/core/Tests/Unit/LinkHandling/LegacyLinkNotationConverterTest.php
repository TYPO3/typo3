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

use TYPO3\CMS\Core\LinkHandling\LegacyLinkNotationConverter;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\MathUtility;

class LegacyLinkNotationConverterTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * Data to resolve strings to arrays and vice versa, external, mail, page
     *
     * @return array
     */
    public function resolveParametersForNonFilesDataProvider()
    {
        return [
            'simple page - old style' => [
                // original input value
                '13',
                // splitted values
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pageuid' => 13
                ],
                // final unified URN
                't3://page?uid=13'
            ],
            'page with type - old style' => [
                '13,31',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pageuid' => 13,
                    'pagetype' => 31
                ],
                't3://page?uid=13&type=31'
            ],
            'page with type and fragment - old style' => [
                '13,31#uncool',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pageuid' => '13',
                    'pagetype' => '31',
                    'fragment' => 'uncool'
                ],
                't3://page?uid=13&type=31#uncool'
            ],
            'page with type and parameters and fragment - old style' => [
                '13,31?unbel=ievable#uncool',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pageuid' => '13',
                    'pagetype' => '31',
                    'parameters' => 'unbel=ievable',
                    'fragment' => 'uncool'
                ],
                't3://page?uid=13&type=31&unbel=ievable#uncool'
            ],
            'page with alias - old style' => [
                'alias13',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pagealias' => 'alias13'
                ],
                't3://page?alias=alias13'
            ]
        ];
    }

    /**
     * @test
     *
     * @param string $input
     * @param array  $expected
     * @param string $finalString
     *
     * @dataProvider resolveParametersForNonFilesDataProvider
     */
    public function resolveReturnsSplitParameters($input, $expected, $finalString)
    {
        $subject = new LegacyLinkNotationConverter();
        $this->assertEquals($expected, $subject->resolve($input));
    }

    /**
     * @test
     *
     * @param string $input
     * @param array  $parameters
     * @param string $expected
     *
     * @dataProvider resolveParametersForNonFilesDataProvider
     */
    public function splitParametersToUnifiedIdentifier($input, $parameters, $expected)
    {
        $subject = new LinkService();
        $this->assertEquals($expected, $subject->asString($parameters));
    }

    /**
     * testing files and folders
     */

    /**
     * Data provider for pointing to files
     * t3:file:15
     * t3:file:fileadmin/deep/down.jpg
     * t3:file:1:myfolder/myidentifier.jpg
     * t3:folder:1:myfolder
     *
     * @return array
     */
    public function resolveParametersForFilesDataProvider()
    {
        return [
            'file without FAL - VERY old style' => [
                'fileadmin/on/steroids.png',
                [
                    'type' => LinkService::TYPE_FILE,
                    'file' => 'fileadmin/on/steroids.png'
                ],
                't3://file?identifier=fileadmin%2Fon%2Fsteroids.png'
            ],
            'file without FAL with file prefix - VERY old style' => [
                'file:fileadmin/on/steroids.png',
                [
                    'type' => LinkService::TYPE_FILE,
                    'file' => 'fileadmin/on/steroids.png'
                ],
                't3://file?identifier=fileadmin%2Fon%2Fsteroids.png'
            ],
            'file with FAL uid - old style' => [
                'file:23',
                [
                    'type' => LinkService::TYPE_FILE,
                    'file' => 23
                ],
                't3://file?uid=23'
            ],
            'folder without FAL - VERY old style' => [
                'fileadmin/myimages/',
                [
                    'type' => LinkService::TYPE_FOLDER,
                    'folder' => 'fileadmin/myimages/'
                ],
                't3://folder?storage=0&identifier=%2Ffileadmin%2Fmyimages%2F'
            ],
            'folder with combined identifier and file prefix (FAL) - old style' => [
                'file:2:/myimages/',
                [
                    'type' => LinkService::TYPE_FOLDER,
                    'folder' => '2:/myimages/'
                ],
                't3://folder?storage=2&identifier=%2Fmyimages%2F'
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
        $storage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $factory = $this->getMockBuilder(ResourceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        // fake methods to return proper objects
        if ($expected['type'] === LinkService::TYPE_FILE) {
            $fileObject = new File(['identifier' => $expected['file']], $storage);
            $factory->expects($this->any())->method('getFileObjectFromCombinedIdentifier')->with($expected['file'])
                ->willReturn($fileObject);
            $factory->expects($this->any())->method('retrieveFileOrFolderObject')->with($expected['file'])
                ->willReturn($fileObject);
            $factory->expects($this->any())->method('getFileObject')->with($expected['file'])->willReturn($fileObject);
            $expected['file'] = $fileObject;
        }
        // fake methods to return proper objects
        if ($expected['type'] === LinkService::TYPE_FOLDER) {
            if (substr($expected['folder'], 0, 5) === 'file:') {
                $expected['folder'] = substr($expected['folder'], 5);
            }
            $folderObject = new Folder($storage, $expected['folder'], $expected['folder']);
            $factory->expects($this->any())->method('retrieveFileOrFolderObject')->with($expected['folder'])
                ->willReturn($folderObject);
            $factory->expects($this->any())->method('getFolderObjectFromCombinedIdentifier')->with($expected['folder'])
                ->willReturn($folderObject);
            $expected['folder'] = $folderObject;
        }

        /** @var LegacyLinkNotationConverter|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(LegacyLinkNotationConverter::class, ['dummy']);
        $subject->_set('resourceFactory', $factory);
        $this->assertEquals($expected, $subject->resolve($input));
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
        // fake methods to return proper objects
        if ($parameters['type'] === LinkService::TYPE_FILE) {
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
        }
        // fake methods to return proper objects
        if ($parameters['type'] === LinkService::TYPE_FOLDER) {
            if (substr($parameters['folder'], 0, 5) === 'file:') {
                $parameters['folder'] = substr($parameters['folder'], 5);
            }
            // fake "0" storage
            if (!MathUtility::canBeInterpretedAsInteger($parameters['folder']{0})) {
                $parameters['folder'] = '0:' . $parameters['folder'];
            }
            $folderObject = $this->getMockBuilder(Folder::class)
                ->setMethods(['getCombinedIdentifier', 'getStorage', 'getIdentifier'])
                ->disableOriginalConstructor()
                ->getMock();
            $folderObject->expects($this->any())->method('getCombinedIdentifier')->willReturn($parameters['folder']);
            $folderData = explode(':', $parameters['folder']);
            /** @var ResourceStorage|\PHPUnit_Framework_MockObject_MockObject $storageMock */
            $storage = $this->getMockBuilder(ResourceStorage::class)
                ->disableOriginalConstructor()
                ->getMock(['getUid']);
            $storage->method('getUid')->willReturn($folderData[0]);
            $folderObject->expects($this->any())->method('getStorage')->willReturn($storage);
            $folderObject->expects($this->any())->method('getIdentifier')->willReturn($folderData[1]);
            $parameters['folder'] = $folderObject;
        }

        $subject = new LinkService();
        $this->assertEquals($expected, $subject->asString($parameters));
    }
}
