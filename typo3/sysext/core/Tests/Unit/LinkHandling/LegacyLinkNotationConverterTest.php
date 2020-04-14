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

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\LinkHandling\LegacyLinkNotationConverter;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class LegacyLinkNotationConverterTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSingletonInstances = true;
    }

    /**
     * Data to resolve strings to arrays and vice versa, external, mail, page
     *
     * @return array
     */
    public function resolveParametersForNonFilesDataProvider(): array
    {
        return [
            'simple page - old style' => [
                // original input value
                '13',
                // split values
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
            'page with type and parameters as another parameter and fragment - old style' => [
                '13,31,&unbel=ievable&but=possibly#uncool',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pageuid' => '13',
                    'pagetype' => '31',
                    'parameters' => 'unbel=ievable&but=possibly',
                    'fragment' => 'uncool'
                ],
                't3://page?uid=13&type=31&unbel=ievable&but=possibly#uncool'
            ],
            'page with type and parameters as third parameter and explicitly allow type=0' => [
                '1,0,&param=2',
                [
                    'type' => LinkService::TYPE_PAGE,
                    'pageuid' => '1',
                    'pagetype' => '0',
                    'parameters' => 'param=2',
                ],
                't3://page?uid=1&type=0&param=2'
            ],
            'record of table - old 2-part identifier' => [
                'record:tx_myext_entity:456',
                [
                    'type' => LinkService::TYPE_RECORD,
                    'identifier' => 'tx_myext_entity',
                    'table' => 'tx_myext_entity',
                    'uid' => 456,
                    'url' => 'record:tx_myext_entity:456',
                    'value' => 'tx_myext_entity:456'
                ],
                't3://record?identifier=tx_myext_entity&uid=456'
            ],
            'record of table - old 3-part identifier' => [
                'record:usage1:tx_myext_entity:456',
                [
                    'type' => LinkService::TYPE_RECORD,
                    'identifier' => 'usage1',
                    'table' => 'tx_myext_entity',
                    'uid' => 456,
                    'url' => 'record:usage1:tx_myext_entity:456',
                    'value' => 'usage1:tx_myext_entity:456'
                ],
                't3://record?identifier=usage1&uid=456'
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $input
     * @param array  $expected
     *
     * @dataProvider resolveParametersForNonFilesDataProvider
     */
    public function resolveReturnsSplitParameters($input, $expected): void
    {
        $subject = new LegacyLinkNotationConverter();
        self::assertEquals($expected, $subject->resolve($input));
    }

    /**
     * @test
     *
     * @param string $input
     * @param array $parameters
     * @param string $expected
     *
     * @throws \TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException
     * @dataProvider resolveParametersForNonFilesDataProvider
     */
    public function splitParametersToUnifiedIdentifier(string $input, array $parameters, string $expected): void
    {
        $subject = new LinkService();
        self::assertEquals($expected, $subject->asString($parameters));
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
    public function resolveParametersForFilesDataProvider(): array
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
            'file without FAL and anchor - VERY old style' => [
                'fileadmin/on/steroids.png#page-23',
                [
                    'type' => LinkService::TYPE_FILE,
                    'file' => 'fileadmin/on/steroids.png',
                    'fragment' => 'page-23'
                ],
                't3://file?identifier=fileadmin%2Fon%2Fsteroids.png#page-23'
            ],
            'file without FAL with file prefix - VERY old style' => [
                'file:fileadmin/on/steroids.png',
                [
                    'type' => LinkService::TYPE_FILE,
                    'file' => 'fileadmin/on/steroids.png'
                ],
                't3://file?identifier=fileadmin%2Fon%2Fsteroids.png'
            ],
            'file without FAL with file prefix and anchor - VERY old style' => [
                'file:fileadmin/on/steroids.png#page-13',
                [
                    'type' => LinkService::TYPE_FILE,
                    'file' => 'fileadmin/on/steroids.png',
                    'fragment' => 'page-13'
                ],
                't3://file?identifier=fileadmin%2Fon%2Fsteroids.png#page-13'
            ],
            'file with FAL uid - old style' => [
                'file:23',
                [
                    'type' => LinkService::TYPE_FILE,
                    'file' => 23
                ],
                't3://file?uid=23'
            ],
            'file with FAL uid and anchor - old style' => [
                'file:23#page-13',
                [
                    'type' => LinkService::TYPE_FILE,
                    'file' => 23,
                    'fragment' => 'page-13',
                ],
                't3://file?uid=23#page-13'
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
     *
     * @dataProvider resolveParametersForFilesDataProvider
     */
    public function resolveFileReferencesToSplitParameters($input, $expected): void
    {
        /** @var ResourceStorage|MockObject $storage */
        $storage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $factory = $this->getMockBuilder(ResourceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        // fake methods to return proper objects
        if ($expected['type'] === LinkService::TYPE_FILE) {
            $fileObject = new File(['identifier' => $expected['file']], $storage);
            $factory->method('getFileObjectFromCombinedIdentifier')->with($expected['file'])
                ->willReturn($fileObject);
            $factory->method('retrieveFileOrFolderObject')->with($expected['file'])
                ->willReturn($fileObject);
            $factory->method('getFileObject')->with($expected['file'])->willReturn($fileObject);
            $expected['file'] = $fileObject;
        }
        // fake methods to return proper objects
        if ($expected['type'] === LinkService::TYPE_FOLDER) {
            if (strpos($expected['folder'], 'file:') === 0) {
                $expected['folder'] = substr($expected['folder'], 5);
            }
            $folderObject = new Folder($storage, $expected['folder'], $expected['folder']);
            $factory->method('retrieveFileOrFolderObject')->with($expected['folder'])
                ->willReturn($folderObject);
            $factory->method('getFolderObjectFromCombinedIdentifier')->with($expected['folder'])
                ->willReturn($folderObject);
            $expected['folder'] = $folderObject;
        }
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $factory);

        $subject = new LegacyLinkNotationConverter();

        self::assertEquals($expected, $subject->resolve($input));
    }

    /**
     * Helpful to know in which if() clause the stuff gets in
     *
     * @test
     *
     * @param string $input
     * @param array $parameters
     * @param string $expected
     *
     * @throws \TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException
     * @dataProvider resolveParametersForFilesDataProvider
     */
    public function splitParametersToUnifiedIdentifierForFiles(string $input, array $parameters, string $expected): void
    {
        // fake methods to return proper objects
        if ($parameters['type'] === LinkService::TYPE_FILE) {
            $fileObject = $this->getMockBuilder(File::class)
                ->onlyMethods(['getUid', 'getIdentifier'])
                ->disableOriginalConstructor()
                ->getMock();
            $uid = 0;
            if (MathUtility::canBeInterpretedAsInteger($parameters['file'])) {
                $uid = $parameters['file'];
            }
            $fileObject->expects(self::once())->method('getUid')->willReturn($uid);
            $fileObject->expects(self::any())->method('getIdentifier')->willReturn($parameters['file']);
            $parameters['file'] = $fileObject;
        }
        // fake methods to return proper objects
        if ($parameters['type'] === LinkService::TYPE_FOLDER) {
            if (strpos($parameters['folder'], 'file:') === 0) {
                $parameters['folder'] = substr($parameters['folder'], 5);
            }
            // fake "0" storage
            if (!MathUtility::canBeInterpretedAsInteger($parameters['folder'][0])) {
                $parameters['folder'] = '0:' . $parameters['folder'];
            }
            $folderObject = $this->getMockBuilder(Folder::class)
                ->onlyMethods(['getCombinedIdentifier', 'getStorage', 'getIdentifier'])
                ->disableOriginalConstructor()
                ->getMock();
            $folderObject->expects(self::any())->method('getCombinedIdentifier')->willReturn($parameters['folder']);
            $folderData = explode(':', $parameters['folder']);
            /** @var ResourceStorage|\PHPUnit\Framework\MockObject\MockObject $storageMock */
            $storage = $this->getMockBuilder(ResourceStorage::class)
                ->onlyMethods(['getUid'])
                ->disableOriginalConstructor()
                ->getMock();
            $storage->method('getUid')->willReturn($folderData[0]);
            $folderObject->method('getStorage')->willReturn($storage);
            $folderObject->method('getIdentifier')->willReturn($folderData[1]);
            $parameters['folder'] = $folderObject;
        }

        $subject = new LinkService();
        self::assertEquals($expected, $subject->asString($parameters));
    }

    /**
     * @return array
     */
    public function resolveThrowExceptionWithPharReferencesDataProvider(): array
    {
        return [
            'URL encoded local' => [
                'phar%3a//some-file.jpg',
            ],
            'URL encoded absolute' => [
                'phar%3a///path/some-file.jpg',
            ],
            'not URL encoded local' => [
                'phar://some-file.jpg',
            ],
            'not URL encoded absolute' => [
                'phar:///path/some-file.jpg',
            ],
        ];
    }

    /**
     * @test
     *
     * @param string $pharUrl
     *
     * @dataProvider resolveThrowExceptionWithPharReferencesDataProvider
     */
    public function resolveThrowExceptionWithPharReferences(string $pharUrl): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1530030673);
        (new LegacyLinkNotationConverter())->resolve($pharUrl);
    }
}
