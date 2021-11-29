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

namespace Unit\DataHandling\SoftReference;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Tests\Unit\DataHandling\SoftReference\AbstractSoftReferenceParserTest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TypoLinkTagSoftReferenceParserTest extends AbstractSoftReferenceParserTest
{
    public function findRefReturnsParsedElementsDataProvider(): array
    {
        return [
            'link to page' => [
                [
                    'content' => '<p><a href="t3://page?uid=42">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a href="t3://page?uid=42">',
                ],
                [
                    'subst' => [
                        'type' => 'db',
                        'recordRef' => 'pages:42',
                        'tokenValue' => 42,
                    ],
                ],
            ],
            'link to page with properties' => [
                [
                    'content' => '<p><a class="link-page" href="t3://page?uid=42" target="_top" title="Foo">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a class="link-page" href="t3://page?uid=42" target="_top" title="Foo">',
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
                    'content' => '<p><a class="link-page" href="www.example.com" target="_top" title="Foo">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a class="link-page" href="www.example.com" target="_top" title="Foo">',
                ],
                [
                    'subst' => [
                        'type' => 'external',
                        'tokenValue' => 'http://www.example.com',
                    ],
                ],
            ],
            'link to external URL with scheme' => [
                'typolink_tag' => [
                    'content' => '<p><a class="link-page" href="https://www.example.com" target="_top" title="Foo">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a class="link-page" href="https://www.example.com" target="_top" title="Foo">',
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
                    'content' => '<p><a href="mailto:test@example.com">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a href="mailto:test@example.com">',
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
                    'content' => '<p><a href="test@example.com">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a href="test@example.com">',
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
                    'content' => '<p><a href="tel:0123456789">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a href="tel:0123456789">',
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
        $subject = $this->getParserByKey('typolink_tag');
        $subject->setParserKey('typolink_tag', $softrefConfiguration);
        $result = $subject->parse(
            'tt_content',
            'bodytext',
            1,
            $softrefConfiguration['content']
        );

        $matchedElements = $result->getMatchedElements();
        self::assertTrue($result->hasMatched());

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
                    'content' => '<p><a href="t3://file?uid=42">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a href="t3://file?uid=42">',
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

        $subject = $this->getParserByKey('typolink_tag');
        $subject->setParserKey('typolink_tag', $softrefConfiguration);
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
                    'content' => '<p><a href="t3://folder?storage=1&amp;identifier=%2Ffoo%2Fbar%2Fbaz">Click here</a></p>',
                ],
            ],
            'link to folder with properties' => [
                [
                    'content' => '<p><a class="link-page" href="t3://folder?storage=1&amp;identifier=%2Ffoo%2Fbar%2Fbaz" target="_top" title="Foo">Click here</a></p>',
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

        $result = $this->getParserByKey('typolink_tag')->parse(
            'tt_content',
            'bodytext',
            1,
            $softrefConfiguration['content']
        );

        self::assertFalse($result->hasMatched());
    }
}
