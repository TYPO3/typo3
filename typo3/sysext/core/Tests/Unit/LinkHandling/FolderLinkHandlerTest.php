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

namespace TYPO3\CMS\Core\Tests\Unit\LinkHandling;

use TYPO3\CMS\Core\LinkHandling\FolderLinkHandler;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FolderLinkHandlerTest extends UnitTestCase
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
    public function resolveParametersForFilesDataProvider(): array
    {
        return [
            'folder without FAL - cool style' => [
                [
                    'storage' => 0,
                    'identifier' => '/fileadmin/myimages/'
                ],
                [
                    'folder' => '0:/fileadmin/myimages/'
                ],
                't3://folder?storage=0&identifier=%2Ffileadmin%2Fmyimages%2F'
            ],
            'folder with combined identifier and file prefix (FAL) - cool style' => [
                [
                    'storage' => 2,
                    'identifier' => '/myimages/'
                ],
                [
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
     * @param array $input
     * @param array  $expected
     *
     * @dataProvider resolveParametersForFilesDataProvider
     */
    public function resolveFileReferencesToSplitParameters(array $input, array $expected): void
    {
        $storage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $factory = $this->getMockBuilder(ResourceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        // fake methods to return proper objects
        $folderObject = new Folder($storage, $expected['folder'], $expected['folder']);
        $factory->expects(self::once())->method('getFolderObjectFromCombinedIdentifier')->with($expected['folder'])
            ->willReturn($folderObject);
        $expected['folder'] = $folderObject;
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $factory);

        $subject = new FolderLinkHandler();

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
        $folderObject = $this->getMockBuilder(Folder::class)
            ->onlyMethods(['getCombinedIdentifier', 'getStorage', 'getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();

        $folderObject->expects(self::any())->method('getCombinedIdentifier')->willReturn($parameters['folder']);
        $folderData = explode(':', $parameters['folder']);
        /** @var ResourceStorage|\PHPUnit\Framework\MockObject\MockObject $storageMock */
        $storage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock(['getUid']);
        $storage->method('getUid')->willReturn($folderData[0]);
        $folderObject->method('getStorage')->willReturn($storage);
        $folderObject->method('getIdentifier')->willReturn($folderData[1]);
        $parameters['folder'] = $folderObject;

        $subject = new FolderLinkHandler();
        self::assertEquals($expected, $subject->asString($parameters));
    }
}
