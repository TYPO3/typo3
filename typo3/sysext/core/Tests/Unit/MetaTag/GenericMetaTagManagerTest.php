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

namespace TYPO3\CMS\Core\Tests\Unit\MetaTag;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\MetaTag\GenericMetaTagManager;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\DocType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class GenericMetaTagManagerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $pageRenderer = $this->getMockBuilder(PageRenderer::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRenderer);
    }

    #[Test]
    public function checkIfGetAllHandledPropertiesReturnsNonEmptyArray(): void
    {
        $manager = new GenericMetaTagManager();
        $handledProperties = $manager->getAllHandledProperties();

        self::assertEmpty($handledProperties);
    }

    #[Test]
    public function checkIfMethodCanHandlePropertyAlwaysReturnsTrue(): void
    {
        $manager = new GenericMetaTagManager();
        self::assertTrue($manager->canHandleProperty('custom-meta-tag'));
        self::assertTrue($manager->canHandleProperty('description'));
        self::assertTrue($manager->canHandleProperty('og:title'));
    }

    #[DataProvider('propertiesProvider')]
    #[Test]
    public function checkIfPropertyIsStoredAfterAddingProperty($property, $expected, $expectedRenderedTag): void
    {
        $manager = new GenericMetaTagManager();
        $manager->addProperty(
            $property['property'],
            $property['content'],
            (array)$property['subProperties'],
            $property['replace'],
            $property['type']
        );

        self::assertEquals($expected, $manager->getProperty($property['property'], $property['type']));
        self::assertEquals($expectedRenderedTag, $manager->renderProperty($property['property']));
    }

    #[Test]
    public function checkRenderAllPropertiesRendersCorrectMetaTags(): void
    {
        $properties = [
            [
                'property' => 'description',
                'content' => 'This is a description',
                'subProperties' => [],
                'replace' => false,
                'type' => '',
            ],
            [
                'property' => 'og:image',
                'content' => '/path/to/image',
                'subProperties' => [
                    'width' => 400,
                ],
                'replace' => false,
                'type' => 'property',
            ],
            [
                'property' => 'og:image:height',
                'content' => '200',
                'subProperties' => [],
                'replace' => false,
                'type' => 'property',
            ],
            [
                'property' => 'twitter:card',
                'content' => 'This is the Twitter card',
                'subProperties' => [],
                'replace' => false,
                'type' => '',
            ],
            [
                'property' => 'og:image',
                'content' => '/path/to/image2',
                'subProperties' => [],
                'replace' => true,
                'type' => 'property',
            ],
        ];

        $manager = new GenericMetaTagManager();
        foreach ($properties as $property) {
            $manager->addProperty(
                $property['property'],
                $property['content'],
                $property['subProperties'],
                $property['replace'],
                $property['type']
            );
        }

        $expected = '<meta name="description" content="This is a description">' . PHP_EOL .
            '<meta property="og:image" content="/path/to/image2">' . PHP_EOL .
            '<meta property="og:image:height" content="200">' . PHP_EOL .
            '<meta name="twitter:card" content="This is the Twitter card">';

        self::assertEquals($expected, $manager->renderAllProperties());
    }

    #[Test]
    public function checkIfRemovePropertyReallyRemovesProperty(): void
    {
        $manager = new GenericMetaTagManager();
        $manager->addProperty('description', 'Description');
        self::assertEquals([['content' => 'Description', 'subProperties' => []]], $manager->getProperty('description'));

        $manager->removeProperty('description');
        self::assertEquals([], $manager->getProperty('description'));

        $manager->addProperty('description', 'Description 1', [], false, 'property');
        $manager->addProperty('description', 'Description 2', [], false, '');
        $manager->addProperty('description', 'Description 3', []);

        self::assertEquals([['content' => 'Description 1', 'subProperties' => []]], $manager->getProperty('description', 'property'));

        $manager->removeProperty('Description', 'Property');
        self::assertEquals([], $manager->getProperty('description', 'property'));
        self::assertEquals(
            [
                ['content' => 'Description 2', 'subProperties' => []],
                ['content' => 'Description 3', 'subProperties' => []],
            ],
            $manager->getProperty('description')
        );

        $manager->addProperty('description', 'Title', [], false, 'property');
        $manager->addProperty('description', 'Title', [], false, 'name');
        $manager->addProperty('twitter:card', 'Twitter card');

        $manager->removeAllProperties();

        self::assertEquals([], $manager->getProperty('description'));
        self::assertEquals([], $manager->getProperty('description', 'name'));
        self::assertEquals([], $manager->getProperty('description', 'property'));
        self::assertEquals([], $manager->getProperty('twitter:card'));
    }

    public static function propertiesProvider(): array
    {
        return [
            [
                [
                    'property' => 'custom-tag',
                    'content' => 'Test title',
                    'subProperties' => [],
                    'replace' => false,
                    'type' => '',
                ],
                [
                    [
                        'content' => 'Test title',
                        'subProperties' => [],
                    ],
                ],
                '<meta name="custom-tag" content="Test title">',
            ],
            [
                [
                    'property' => 'description',
                    'content' => 'Custom description',
                    'subProperties' => [],
                    'replace' => false,
                    'type' => '',
                ],
                [
                    [
                        'content' => 'Custom description',
                        'subProperties' => [],
                    ],
                ],
                '<meta name="description" content="Custom description">',
            ],
            [
                [
                    'property' => 'og:image',
                    'content' => '/path/to/image',
                    'subProperties' => [],
                    'replace' => false,
                    'type' => 'property',
                ],
                [
                    [
                        'content' => '/path/to/image',
                        'subProperties' => [],
                    ],
                ],
                '<meta property="og:image" content="/path/to/image">',
            ],
            [
                [
                    'property' => 'og:image',
                    'content' => '/path/to/image',
                    'subProperties' => ['width' => 100],
                    'replace' => false,
                    'type' => 'property',
                ],
                [
                    [
                        'content' => '/path/to/image',
                        'subProperties' => ['width' => 100],
                    ],
                ],
                '<meta property="og:image" content="/path/to/image">' . PHP_EOL .
                    '<meta property="og:image:width" content="100">',
            ],
            [
                [
                    'property' => 'og:image:width',
                    'content' => '100',
                    'subProperties' => [],
                    'replace' => false,
                    'type' => 'property',
                ],
                [
                    [
                        'content' => '100',
                        'subProperties' => [],
                    ],
                ],
                '<meta property="og:image:width" content="100">',
            ],
        ];
    }

    #[Test]
    public function checkRenderAllPropertiesUsesCorrectEndingSlash(): void
    {
        $pageRenderer = $this->getMockBuilder(PageRenderer::class)->onlyMethods(['getDocType'])->disableOriginalConstructor()->getMock();
        $pageRenderer->expects($this->once())->method('getDocType')->willReturn(DocType::xhtml11);
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRenderer);

        $manager = new GenericMetaTagManager();
        $manager->addProperty('description', 'Description');
        self::assertEquals('<meta name="description" content="Description" />', $manager->renderProperty('description'));
    }
}
