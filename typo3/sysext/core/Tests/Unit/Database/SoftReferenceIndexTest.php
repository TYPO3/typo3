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

namespace TYPO3\CMS\Core\Tests\Unit\Database;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\SoftReferenceIndex;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class SoftReferenceIndexTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    public function findRefReturnsParsedElementsDataProvider(): array
    {
        return [
            'link to page' => [
                [
                    'typolink' => [
                        'content' => 't3://page?uid=42',
                        'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                        'matchString' => 't3://page?uid=42',
                    ],
                    'typolink_tag' => [
                        'content' => '<p><a href="t3://page?uid=42">Click here</a></p>',
                        'elementKey' => 1,
                        'matchString' => '<a href="t3://page?uid=42">',
                    ],
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
                    'typolink_tag' => [
                        'content' => '<p><a class="link-page" href="t3://page?uid=42" target="_top" title="Foo">Click here</a></p>',
                        'elementKey' => 1,
                        'matchString' => '<a class="link-page" href="t3://page?uid=42" target="_top" title="Foo">',
                    ],
                ],
                [
                    'subst' => [
                        'type' => 'db',
                        'recordRef' => 'pages:42',
                        'tokenValue' => '42',
                    ],
                ],
            ],
            'link to page with just a number' => [
                [
                    'typolink' => [
                        'content' => '42',
                        'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                        'matchString' => '42',
                    ],
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
                    'typolink' => [
                        'content' => '42,100',
                        'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                        'matchString' => '42,100',
                    ],
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
                    'typolink' => [
                        'content' => 'www.example.com',
                        'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                        'matchString' => 'www.example.com',
                    ],
                    'typolink_tag' => [
                        'content' => '<p><a class="link-page" href="www.example.com" target="_top" title="Foo">Click here</a></p>',
                        'elementKey' => 1,
                        'matchString' => '<a class="link-page" href="www.example.com" target="_top" title="Foo">',
                    ],
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
                    'typolink' => [
                        'content' => 'https://www.example.com',
                        'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                        'matchString' => 'https://www.example.com',
                    ],
                    'typolink_tag' => [
                        'content' => '<p><a class="link-page" href="https://www.example.com" target="_top" title="Foo">Click here</a></p>',
                        'elementKey' => 1,
                        'matchString' => '<a class="link-page" href="https://www.example.com" target="_top" title="Foo">',
                    ],
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
                    'typolink' => [
                        'content' => 'mailto:test@example.com',
                        'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                        'matchString' => 'mailto:test@example.com',
                    ],
                    'typolink_tag' => [
                        'content' => '<p><a href="mailto:test@example.com">Click here</a></p>',
                        'elementKey' => 1,
                        'matchString' => '<a href="mailto:test@example.com">',
                    ],
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
                    'typolink' => [
                        'content' => 'test@example.com',
                        'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                        'matchString' => 'test@example.com',
                    ],
                    'typolink_tag' => [
                        'content' => '<p><a href="test@example.com">Click here</a></p>',
                        'elementKey' => 1,
                        'matchString' => '<a href="test@example.com">',
                    ],
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
                    'typolink' => [
                        'content' => 'tel:0123456789',
                        'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                        'matchString' => 'tel:0123456789',
                    ],
                    'typolink_tag' => [
                        'content' => '<p><a href="tel:0123456789">Click here</a></p>',
                        'elementKey' => 1,
                        'matchString' => '<a href="tel:0123456789">',
                    ],
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
    public function findRefReturnsParsedElements(array $softrefConfiguration, array $expectedElement)
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        foreach ($softrefConfiguration as $softrefKey => $configuration) {
            $subject = new SoftReferenceIndex($eventDispatcher->reveal());
            $result = $subject->findRef(
                'tt_content',
                'bodytext',
                1,
                $configuration['content'],
                $softrefKey,
                []
            );

            self::assertArrayHasKey('elements', $result);
            self::assertArrayHasKey($configuration['elementKey'], $result['elements']);

            // Remove tokenID as this one depends on the softrefKey and doesn't need to be verified
            unset($result['elements'][$configuration['elementKey']]['subst']['tokenID']);

            $expectedElement['matchString'] = $configuration['matchString'];
            self::assertEquals($expectedElement, $result['elements'][$configuration['elementKey']]);
        }
    }

    public function findRefReturnsParsedElementsWithFileDataProvider(): array
    {
        return [
            'link to file' => [
                [
                    'typolink' => [
                        'content' => 't3://file?uid=42',
                        'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                        'matchString' => 't3://file?uid=42',
                    ],
                    'typolink_tag' => [
                        'content' => '<p><a href="t3://file?uid=42">Click here</a></p>',
                        'elementKey' => 1,
                        'matchString' => '<a href="t3://file?uid=42">',
                    ],
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
                    'typolink' => [
                        'content' => 't3://file?identifier=42',
                        'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                        'matchString' => 't3://file?identifier=42',
                    ],
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
                    'typolink' => [
                        'content' => 'file:42',
                        'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                        'matchString' => 'file:42',
                    ],
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
                    'typolink' => [
                        'content' => 'fileadmin/download.jpg',
                        'elementKey' => '8695f308356bcca1acac2749152a44a9:0',
                        'matchString' => 'fileadmin/download.jpg',
                    ],
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
    public function findRefReturnsParsedElementsWithFile(array $softrefConfiguration, array $expectedElement)
    {
        $fileObject = $this->prophesize(File::class);
        $fileObject->getUid()->willReturn(42)->shouldBeCalledTimes(count($softrefConfiguration));

        $resourceFactory = $this->prophesize(ResourceFactory::class);
        $resourceFactory->getFileObject('42')->willReturn($fileObject->reveal());
        // For `t3://file?identifier=42` handling
        $resourceFactory->getFileObjectFromCombinedIdentifier('42')->willReturn($fileObject->reveal());
        // For `file:23` handling
        $resourceFactory->retrieveFileOrFolderObject('42')->willReturn($fileObject->reveal());
        // For `fileadmin/download.jpg` handling
        $resourceFactory->retrieveFileOrFolderObject('fileadmin/download.jpg')->willReturn($fileObject->reveal());

        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory->reveal());

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        foreach ($softrefConfiguration as $softrefKey => $configuration) {
            $subject = new SoftReferenceIndex($eventDispatcher->reveal());
            $result = $subject->findRef(
                'tt_content',
                'bodytext',
                1,
                $configuration['content'],
                $softrefKey,
                []
            );

            self::assertArrayHasKey('elements', $result);
            self::assertArrayHasKey($configuration['elementKey'], $result['elements']);

            // Remove tokenID as this one depends on the softrefKey and doesn't need to be verified
            unset($result['elements'][$configuration['elementKey']]['subst']['tokenID']);

            $expectedElement['matchString'] = $configuration['matchString'];
            self::assertEquals($expectedElement, $result['elements'][$configuration['elementKey']]);
        }
    }

    public function findRefReturnsNullWithFolderDataProvider(): array
    {
        return [
            'link to folder' => [
                [
                    'typolink' => [
                        'content' => 't3://folder?storage=1&amp;identifier=%2Ffoo%2Fbar%2Fbaz',
                    ],
                    'typolink_tag' => [
                        'content' => '<p><a href="t3://folder?storage=1&amp;identifier=%2Ffoo%2Fbar%2Fbaz">Click here</a></p>',
                    ],
                ],
            ],
            'link to folder with properties' => [
                [
                    'typolink_tag' => [
                        'content' => '<p><a class="link-page" href="t3://folder?storage=1&amp;identifier=%2Ffoo%2Fbar%2Fbaz" target="_top" title="Foo">Click here</a></p>',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider findRefReturnsNullWithFolderDataProvider
     * @param array $softrefConfiguration
     */
    public function findRefReturnsNullWithFolder(array $softrefConfiguration)
    {
        $folderObject = $this->prophesize(Folder::class);

        $resourceFactory = $this->prophesize(ResourceFactory::class);
        $resourceFactory->getFolderObjectFromCombinedIdentifier('1:/foo/bar/baz')->willReturn($folderObject->reveal())->shouldBeCalledTimes(count($softrefConfiguration));
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory->reveal());

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        foreach ($softrefConfiguration as $softrefKey => $configuration) {
            $subject = new SoftReferenceIndex($eventDispatcher->reveal());
            $result = $subject->findRef(
                'tt_content',
                'bodytext',
                1,
                $configuration['content'],
                $softrefKey,
                []
            );

            self::assertNull($result);
        }
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
    public function getTypoLinkPartsThrowExceptionWithPharReferences(string $pharUrl)
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1530030672);
        (new SoftReferenceIndex($eventDispatcher->reveal()))->getTypoLinkParts($pharUrl);
    }
}
