<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Database;

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

use Prophecy\Argument;
use TYPO3\CMS\Core\Database\SoftReferenceIndex;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class SoftReferenceIndexTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * @return array
     */
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
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1530030672);
        (new SoftReferenceIndex())->getTypoLinkParts($pharUrl);
    }

    public function referencesDataProvider(): array
    {
        return [
            'email without schema' => [
                'test@example.com',
                [
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                    'type' => 'email',
                    'email' => 'test@example.com'
                ]
            ],
            'email with schema' => [
                'mailto:test@example.com',
                [
                    'type' => 'email',
                    'email' => 'test@example.com',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ]
            ],
            'link to external URL without scheme' => [
                'www.example.com',
                [
                    'type' => 'url',
                    'url' => 'http://www.example.com',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ]
            ],
            'link to external URL with scheme' => [
                'https://www.example.com',
                [
                    'type' => 'url',
                    'url' => 'https://www.example.com',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ]
            ],
            'link to page with just a number' => [
                '42',
                [
                    'type' => 'page',
                    'pageuid' => 42,
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ]
            ],
            'link to page with just a number and type comma-separated' => [
                '42,100',
                [
                    'type' => 'page',
                    'pageuid' => 42,
                    'pagetype' => 100,
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ]
            ],
            'link to page with t3 syntax' => [
                't3://page?uid=42',
                [
                    'type' => 'page',
                    'pageuid' => 42,
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider referencesDataProvider
     */
    public function getTypoLinkPartsFindsSupportedSchemes(string $input, array $expected)
    {
        $signalSlotDispatcher = $this->prophesize(Dispatcher::class);
        $signalSlotDispatcher->dispatch(Argument::cetera());
        GeneralUtility::setSingletonInstance(Dispatcher::class, $signalSlotDispatcher->reveal());

        $subject = new SoftReferenceIndex();
        $result = $subject->getTypoLinkParts($input, []);
        self::assertEquals($expected, $result);
    }

    public function fileDataProvider(): array
    {
        return [
            'link to file with simple file path' => [
                'fileadmin/download.jpg',
                [
                    'type' => 'file',
                    'file' => '23',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ]
            ],
            'file with old FAL object syntax' => [
                'file:23',
                [
                    'type' => 'file',
                    'file' => '23',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ]
            ],
            'file with t3 mixed syntax' => [
                't3://file?identifier=23',
                [
                    'type' => 'file',
                    'file' => '23',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ]
            ],
            'file with t3 syntax' => [
                't3://file?uid=23',
                [
                    'type' => 'file',
                    'file' => '23',
                    'target' => '',
                    'class' => '',
                    'title' => '',
                    'additionalParams' => '',
                ]
            ],

        ];
    }
    /**
     * @test
     * @dataProvider fileDataProvider
     */
    public function getTypoLinkPartsResolvesFalResources(string $input, array $expected)
    {
        $signalSlotDispatcher = $this->prophesize(Dispatcher::class);
        $signalSlotDispatcher->dispatch(Argument::cetera());
        GeneralUtility::setSingletonInstance(Dispatcher::class, $signalSlotDispatcher->reveal());

        $fileObject = $this->prophesize(File::class);
        $fileObject->getUid()->willReturn(23);
        $resourceFactory = $this->prophesize(ResourceFactory::class);
        $resourceFactory->getFileObject(Argument::cetera())->willReturn($fileObject->reveal());
        $resourceFactory->getFileObjectFromCombinedIdentifier(Argument::cetera())->willReturn($fileObject->reveal());
        $resourceFactory->retrieveFileOrFolderObject(Argument::cetera())->willReturn($fileObject->reveal());
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory->reveal());

        $subject = new SoftReferenceIndex();
        $result = $subject->getTypoLinkParts($input, []);
        self::assertEquals($expected, $result);
    }
}
