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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class TypoLinkTagSoftReferenceParserTest extends AbstractSoftReferenceParserTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $container = new Container();
        $container->set(EventDispatcherInterface::class, new NoopEventDispatcher());
        GeneralUtility::setContainer($container);
    }

    public static function findRefReturnsParsedElementsDataProvider(): array
    {
        return [
            'link to page' => [
                'softrefConfiguration' => [
                    'content' => '<p><a href="t3://page?uid=42">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a href="t3://page?uid=42">',
                ],
                'expectedElement' => [
                    'subst' => [
                        'type' => 'db',
                        'recordRef' => 'pages:42',
                        'tokenValue' => 42,
                    ],
                ],
            ],
            'link to page with properties' => [
                'softrefConfiguration' => [
                    'content' => '<p><a class="link-page" href="t3://page?uid=42" target="_top" title="Foo">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a class="link-page" href="t3://page?uid=42" target="_top" title="Foo">',
                ],
                'expectedElement' => [
                    'subst' => [
                        'type' => 'db',
                        'recordRef' => 'pages:42',
                        'tokenValue' => '42',
                    ],
                ],
            ],
            'link to page with properties and additional query parameters' => [
                'softrefConfiguration' => [
                    'content' => '<p><a class="link-page" href="t3://page?uid=42&L=13&type=123" target="_top" title="Foo">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a class="link-page" href="t3://page?uid=42&L=13&type=123" target="_top" title="Foo">',
                ],
                'expectedElement' => [
                    'subst' => [
                        'type' => 'db',
                        'recordRef' => 'pages:42',
                        'tokenValue' => '42',
                    ],
                ],
            ],
            'link to page with properties and additional query parameters and fragment' => [
                'softrefConfiguration' => [
                    'content' => '<p><a class="link-page" href="t3://page?uid=42&L=13&type=123#953" target="_top" title="Foo">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a class="link-page" href="t3://page?uid=42&L=13&type=123#953" target="_top" title="Foo">',
                ],
                'expectedElement' => [
                    'subst' => [
                        'type' => 'db',
                        'recordRef' => 'pages:42',
                        'tokenValue' => '42',
                    ],
                ],
                'amountOfMatches' => 2,
            ],
            'link to external URL without scheme' => [
                'softrefConfiguration' => [
                    'content' => '<p><a class="link-page" href="www.example.com" target="_top" title="Foo">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a class="link-page" href="www.example.com" target="_top" title="Foo">',
                ],
                'expectedElement' => [
                    'subst' => [
                        'type' => 'external',
                        'tokenValue' => 'http://www.example.com',
                    ],
                ],
            ],
            'link to external URL with scheme' => [
                'softrefConfiguration' => [
                    'content' => '<p><a class="link-page" href="https://www.example.com" target="_top" title="Foo">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a class="link-page" href="https://www.example.com" target="_top" title="Foo">',
                ],
                'expectedElement' => [
                    'subst' => [
                        'type' => 'external',
                        'tokenValue' => 'https://www.example.com',
                    ],
                ],
            ],
            'link to email' => [
                'softrefConfiguration' => [
                    'content' => '<p><a href="mailto:test@example.com">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a href="mailto:test@example.com">',
                ],
                'expectedElement' => [
                    'subst' => [
                        'type' => 'string',
                        'tokenValue' => 'test@example.com',
                    ],
                ],
            ],
            'link to email without schema' => [
                'softrefConfiguration' => [
                    'content' => '<p><a href="test@example.com">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a href="test@example.com">',
                ],
                'expectedElement' => [
                    'subst' => [
                        'type' => 'string',
                        'tokenValue' => 'test@example.com',
                    ],
                ],
            ],
            'link to phone number' => [
                'softrefConfiguration' => [
                    'content' => '<p><a href="tel:0123456789">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a href="tel:0123456789">',
                ],
                'expectedElement' => [
                    'subst' => [
                        'type' => 'string',
                        'tokenValue' => '0123456789',
                    ],
                ],
            ],
            'link with invalid content' => [
                'softrefConfiguration' => [
                    'content' => '<p><a href="Email: hans@example.com">Click here</a></p>',
                    'elementKey' => 1,
                    'matchString' => '<a href="Email: hans@example.com">',
                ],
                'expectedElement' => [
                    'subst' => [
                        'type' => 'string',
                        'tokenValue' => '',
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('findRefReturnsParsedElementsDataProvider')]
    #[Test]
    public function findRefReturnsParsedElements(array $softrefConfiguration, array $expectedElement, int $amountOfMatches = 1): void
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
        self::assertEquals($amountOfMatches, count($matchedElements));

        // Remove tokenID as this one depends on the softrefKey and doesn't need to be verified
        unset($matchedElements[$softrefConfiguration['elementKey']]['subst']['tokenID']);

        $expectedElement['matchString'] = $softrefConfiguration['matchString'];
        self::assertEquals($expectedElement, $matchedElements[$softrefConfiguration['elementKey']]);
    }

    public static function findRefReturnsParsedElementsWithFileDataProvider(): array
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

    #[DataProvider('findRefReturnsParsedElementsWithFileDataProvider')]
    #[Test]
    public function findRefReturnsParsedElementsWithFile(array $softrefConfiguration, array $expectedElement): void
    {
        $fileObject = $this->createMock(File::class);
        $fileObject->expects(self::once())->method('getUid')->willReturn(42);
        $fileObject->expects(self::any())->method('getName')->willReturn('download.jpg');
        $fileObject->expects(self::any())->method('getIdentifier')->willReturn('fileadmin/download.jpg');

        $resourceFactory = $this->createMock(ResourceFactory::class);
        $resourceFactory->method('getFileObject')->with('42')->willReturn($fileObject);
        // For `t3://file?identifier=42` handling
        $resourceFactory->method('getFileObjectFromCombinedIdentifier')->with('42')->willReturn($fileObject);
        // For `file:42` handling
        $resourceFactory->method('retrieveFileOrFolderObject')->with('42')->willReturn($fileObject);

        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory);

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

    public static function findRefReturnsNullWithFolderDataProvider(): array
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

    #[DataProvider('findRefReturnsNullWithFolderDataProvider')]
    #[Test]
    public function findRefReturnsNullWithFolder(array $softrefConfiguration): void
    {
        $folderObject = $this->createMock(Folder::class);

        $resourceFactory = $this->createMock(ResourceFactory::class);
        $resourceFactory->expects(self::once())->method('getFolderObjectFromCombinedIdentifier')
            ->with('1:/foo/bar/baz')->willReturn($folderObject);
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory);

        $result = $this->getParserByKey('typolink_tag')->parse(
            'tt_content',
            'bodytext',
            1,
            $softrefConfiguration['content']
        );

        self::assertFalse($result->hasMatched());
    }
}
