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

namespace TYPO3\CMS\Core\Tests\Unit\DataHandling\SoftReference;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TypoLinkSoftReferenceParserTest extends AbstractSoftReferenceParserTest
{
    public function findRefReturnsParsedElementsDataProvider(): array
    {
        return [
            'link to page' => [
                [
                    'content' => 't3://page?uid=42',
                    'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                    'matchString' => 't3://page?uid=42',
                ],
                [
                    'subst' => [
                        'type' => 'db',
                        'recordRef' => 'pages:42',
                        'tokenValue' => 42,
                    ],
                ],
            ],
            'link to page with just a number' => [
                [
                    'content' => '42',
                    'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                    'matchString' => '42',
                ],
                [
                    'subst' => [
                        'type' => 'db',
                        'recordRef' => 'pages:42',
                        'tokenValue' => '42',
                    ],
                ],
            ],
            'link to page with just a number and type comma-separated' => [
                [
                    'content' => '42,100',
                    'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                    'matchString' => '42,100',
                ],
                [
                    'subst' => [
                        'type' => 'db',
                        'recordRef' => 'pages:42',
                        'tokenValue' => '42',
                    ],
                ],
            ],
            'link to external URL without scheme' => [
                [
                    'content' => 'www.example.com',
                    'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                    'matchString' => 'www.example.com',
                ],
                [
                    'subst' => [
                        'type' => 'external',
                        'tokenValue' => 'http://www.example.com',
                    ],
                ],
            ],
            'link to external URL with scheme' => [
                [
                    'content' => 'https://www.example.com',
                    'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                    'matchString' => 'https://www.example.com',
                ],
                [
                    'subst' => [
                        'type' => 'external',
                        'tokenValue' => 'https://www.example.com',
                    ],
                ],
            ],
            'link to email' => [
                [
                    'content' => 'mailto:test@example.com',
                    'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                    'matchString' => 'mailto:test@example.com',
                ],
                [
                    'subst' => [
                        'type' => 'string',
                        'tokenValue' => 'test@example.com',
                    ],
                ],
            ],
            'link to email without schema' => [
                [
                    'content' => 'test@example.com',
                    'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                    'matchString' => 'test@example.com',
                ],
                [
                    'subst' => [
                        'type' => 'string',
                        'tokenValue' => 'test@example.com',
                    ],
                ],
            ],
            'link to phone number' => [
                [
                    'content' => 'tel:0123456789',
                    'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                    'matchString' => 'tel:0123456789',
                ],
                [
                    'subst' => [
                        'type' => 'string',
                        'tokenValue' => '0123456789',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider findRefReturnsParsedElementsDataProvider
     * @param array $softrefConfiguration
     * @param array $expectedElement
     */
    public function findRefReturnsParsedElements(array $softrefConfiguration, array $expectedElement): void
    {
        $subject = $this->getParserByKey('typolink');
        $subject->setParserKey('typolink', $softrefConfiguration);
        $result = $subject->parse(
            'tt_content',
            'bodytext',
            1,
            $softrefConfiguration['content']
        );
        $matchedElements = $result->getMatchedElements();

        self::assertTrue($result->hasMatched());
        self::assertArrayHasKey($softrefConfiguration['elementKey'], $matchedElements);

        // Remove tokenID as this one depends on the softrefKey and doesn't need to be verified
        unset($matchedElements[$softrefConfiguration['elementKey']]['subst']['tokenID']);

        $expectedElement['matchString'] = $softrefConfiguration['matchString'];
        self::assertEquals($expectedElement, $matchedElements[$softrefConfiguration['elementKey']]);
    }

    public function findRefReturnsParsedElementsWithFileDataProvider(): array
    {
        return [
            'link to file' => [
                [
                    'content' => 't3://file?uid=42',
                    'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                    'matchString' => 't3://file?uid=42',
                ],
                [
                    'subst' => [
                        'type' => 'db',
                        'recordRef' => 'sys_file:42',
                        'tokenValue' => 'file:42',
                    ],
                ],
            ],
            'file with t3 mixed syntax' => [
                [
                    'content' => 't3://file?identifier=42',
                    'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                    'matchString' => 't3://file?identifier=42',
                ],
                [
                    'subst' => [
                        'type' => 'db',
                        'recordRef' => 'sys_file:42',
                        'tokenValue' => 'file:42',
                    ],
                ],
            ],
            'link to file with old FAL object syntax' => [
                [
                    'content' => 'file:42',
                    'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                    'matchString' => 'file:42',
                ],
                [
                    'subst' => [
                        'type' => 'db',
                        'recordRef' => 'sys_file:42',
                        'tokenValue' => 'file:42',
                    ],
                ],
            ],
            'link to file with simple file path' => [
                [
                    'content' => 'fileadmin/download.jpg',
                    'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                    'matchString' => 'fileadmin/download.jpg',
                ],
                [
                    'subst' => [
                        'type' => 'db',
                        'recordRef' => 'sys_file:42',
                        'tokenValue' => 'file:42',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider findRefReturnsParsedElementsWithFileDataProvider
     * @param array $softrefConfiguration
     * @param array $expectedElement
     */
    public function findRefReturnsParsedElementsWithFile(array $softrefConfiguration, array $expectedElement): void
    {
        $fileObject = $this->prophesize(File::class);
        $fileObject->getUid()->willReturn(42)->shouldBeCalledTimes(1);

        $resourceFactory = $this->prophesize(ResourceFactory::class);
        $resourceFactory->getFileObject('42')->willReturn($fileObject->reveal());
        // For `t3://file?identifier=42` handling
        $resourceFactory->getFileObjectFromCombinedIdentifier('42')->willReturn($fileObject->reveal());
        // For `file:23` handling
        $resourceFactory->retrieveFileOrFolderObject('42')->willReturn($fileObject->reveal());
        // For `fileadmin/download.jpg` handling
        $resourceFactory->retrieveFileOrFolderObject('fileadmin/download.jpg')->willReturn($fileObject->reveal());

        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory->reveal());

        $subject = $this->getParserByKey('typolink');
        $subject->setParserKey('typolink', $softrefConfiguration);
        $result = $subject->parse(
            'tt_content',
            'bodytext',
            1,
            $softrefConfiguration['content'],
        );

        $matchedElements = $result->getMatchedElements();
        self::assertTrue($result->hasMatched());

        // Remove tokenID as this one depends on the softrefKey and doesn't need to be verified
        unset($matchedElements[$softrefConfiguration['elementKey']]['subst']['tokenID']);

        $expectedElement['matchString'] = $softrefConfiguration['matchString'];
        self::assertEquals($expectedElement, $matchedElements[$softrefConfiguration['elementKey']]);
    }

    public function findRefReturnsNullWithFolderDataProvider(): array
    {
        return [
            'link to folder' => [
                [
                    'content' => 't3://folder?storage=1&amp;identifier=%2Ffoo%2Fbar%2Fbaz',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider findRefReturnsNullWithFolderDataProvider
     * @param array $softrefConfiguration
     */
    public function findRefReturnsNullWithFolder(array $softrefConfiguration): void
    {
        $folderObject = $this->prophesize(Folder::class);

        $resourceFactory = $this->prophesize(ResourceFactory::class);
        $resourceFactory->getFolderObjectFromCombinedIdentifier('1:/foo/bar/baz')->willReturn($folderObject->reveal())->shouldBeCalledTimes(1);
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory->reveal());

        $result = $this->getParserByKey('typolink')->parse(
            'tt_content',
            'bodytext',
            1,
            $softrefConfiguration['content']
        );

        self::assertFalse($result->hasMatched());
    }

    public function getTypoLinkPartsThrowExceptionWithPharReferencesDataProvider(): array
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
     * @dataProvider getTypoLinkPartsThrowExceptionWithPharReferencesDataProvider
     */
    public function getTypoLinkPartsThrowExceptionWithPharReferences(string $pharUrl): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1530030672);
        $this->getParserByKey('typolink')->parse(
            'tt_content',
            'bodytext',
            1,
            $pharUrl
        );
    }
}
