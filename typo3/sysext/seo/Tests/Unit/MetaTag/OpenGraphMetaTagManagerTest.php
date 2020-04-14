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

namespace TYPO3\CMS\Seo\Tests\Unit\MetaTag;

use TYPO3\CMS\Seo\MetaTag\OpenGraphMetaTagManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class OpenGraphMetaTagManagerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function checkIfGetAllHandledPropertiesReturnsNonEmptyArray()
    {
        $manager = new OpenGraphMetaTagManager();
        $handledProperties = $manager->getAllHandledProperties();

        self::assertNotEmpty($handledProperties);
    }

    /**
     * @dataProvider propertiesProvider
     *
     * @test
     */
    public function checkIfPropertyIsStoredAfterAddingProperty($property, $expected, $expectedRenderedTag)
    {
        $manager = new OpenGraphMetaTagManager();
        $manager->addProperty(
            $property['property'],
            $property['content'],
            (array)$property['subProperties']
        );

        self::assertEquals($expected, $manager->getProperty($property['property']));
        self::assertEquals($expectedRenderedTag, $manager->renderProperty($property['property']));
    }

    /**
     * @test
     */
    public function checkIfAddingOnlySubPropertyAndNoMainPropertyIsReturningException()
    {
        $manager = new OpenGraphMetaTagManager();

        $this->expectException(\UnexpectedValueException::class);
        $manager->addProperty('og:image:width', '400');
    }

    /**
     * @test
     */
    public function checkRenderAllPropertiesRendersCorrectMetaTags()
    {
        $properties = [
            [
                'property' => 'og:title',
                'content' => 'This is a title',
                'subProperties' => [],
                'replace' => false,
                'type' => ''
            ],
            [
                'property' => 'og:image',
                'content' => '/path/to/image',
                'subProperties' => [
                    'width' => 400
                ],
                'replace' => false,
                'type' => ''
            ],
            [
                'property' => 'og:image:height',
                'content' => '200',
                'subProperties' => [],
                'replace' => false,
                'type' => ''
            ],
            [
                'property' => 'og:title',
                'content' => 'This is the new title',
                'subProperties' => [],
                'replace' => true,
                'type' => ''
            ],
            [
                'property' => 'og:image',
                'content' => '/path/to/image2',
                'subProperties' => [],
                'replace' => false,
                'type' => ''
            ],
        ];

        $manager = new OpenGraphMetaTagManager();
        foreach ($properties as $property) {
            $manager->addProperty(
                $property['property'],
                $property['content'],
                $property['subProperties'],
                $property['replace'],
                $property['type']
            );
        }

        $expected = '<meta property="og:image" content="/path/to/image" />' . PHP_EOL .
            '<meta property="og:image:width" content="400" />' . PHP_EOL .
            '<meta property="og:image:height" content="200" />' . PHP_EOL .
            '<meta property="og:image" content="/path/to/image2" />' . PHP_EOL .
            '<meta property="og:title" content="This is the new title" />';

        self::assertEquals($expected, $manager->renderAllProperties());
    }

    /**
     * @test
     */
    public function checkIfRemovePropertyReallyRemovesProperty()
    {
        $manager = new OpenGraphMetaTagManager();
        $manager->addProperty('og:title', 'Title');
        self::assertEquals([['content' => 'Title', 'subProperties' => []]], $manager->getProperty('og:title'));

        $manager->removeProperty('og:title');
        self::assertEquals([], $manager->getProperty('og:title'));

        $manager->addProperty('og:title', 'Title');
        $manager->addProperty('og:description', 'Description');

        $manager->removeAllProperties();

        self::assertEquals([], $manager->getProperty('og:title'));
        self::assertEquals([], $manager->getProperty('og:description'));
    }

    /**
     * @return array
     */
    public function propertiesProvider()
    {
        return [
            [
                [
                    'property' => 'og:title',
                    'content' => 'Test title',
                    'subProperties' => []
                ],
                [
                    [
                        'content' => 'Test title',
                        'subProperties' => []
                    ]
                ],
                '<meta property="og:title" content="Test title" />'
            ],
            [
                [
                    'property' => 'og:image',
                    'content' => '/path/to/image',
                    'subProperties' => []
                ],
                [
                    [
                        'content' => '/path/to/image',
                        'subProperties' => []
                    ]
                ],
                '<meta property="og:image" content="/path/to/image" />'
            ],
            [
                [
                    'property' => 'og:image',
                    'content' => '/path/to/image',
                    'subProperties' => ['width' => [400], 'height' => [400]]
                ],
                [
                    [
                        'content' => '/path/to/image',
                        'subProperties' => [
                            'width' => [400],
                            'height' => [400]
                        ]
                    ]
                ],
                '<meta property="og:image" content="/path/to/image" />' . PHP_EOL .
                '<meta property="og:image:width" content="400" />' . PHP_EOL .
                '<meta property="og:image:height" content="400" />'
            ]
        ];
    }
}
