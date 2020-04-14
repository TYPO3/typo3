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

use TYPO3\CMS\Seo\MetaTag\TwitterCardMetaTagManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TwitterCardMetaTagManagerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function checkIfGetAllHandledPropertiesReturnsNonEmptyArray()
    {
        $manager = new TwitterCardMetaTagManager();
        $handledProperties = $manager->getAllHandledProperties();

        self::assertNotEmpty($handledProperties);
    }

    /**
     * @dataProvider propertiesProvider
     *
     * @test
     *
     * @param array $property
     * @param array $expected
     * @param string $expectedRenderedTag
     */
    public function checkIfPropertyIsStoredAfterAddingProperty(array $property, array $expected, string $expectedRenderedTag)
    {
        $manager = new TwitterCardMetaTagManager();
        $manager->addProperty(
            $property['property'],
            $property['content'],
            (array)$property['subProperties']
        );

        self::assertEquals($expected, $manager->getProperty($property['property']));
        self::assertEquals($expectedRenderedTag, $manager->renderProperty($property['property']));
    }

    /**
     * @return array
     */
    public function propertiesProvider()
    {
        return [
            'title is set' => [
                [
                    'property' => 'twitter:title',
                    'content' => 'Test title',
                    'subProperties' => []
                ],
                [
                    [
                        'content' => 'Test title',
                        'subProperties' => []
                    ]
                ],
                '<meta name="twitter:title" content="Test title" />'
            ],
            'image path is set' => [
                [
                    'property' => 'twitter:image',
                    'content' => '/path/to/image',
                    'subProperties' => []
                ],
                [
                    [
                        'content' => '/path/to/image',
                        'subProperties' => []
                    ]
                ],
                '<meta name="twitter:image" content="/path/to/image" />'
            ],
            'remove not used subproperties' => [
                [
                    'property' => 'twitter:image',
                    'content' => '/path/to/image',
                    'subProperties' => ['width' => [400], 'height' => [400]]
                ],
                [
                    [
                        'content' => '/path/to/image',
                        'subProperties' => []
                    ]
                ],
                '<meta name="twitter:image" content="/path/to/image" />'
            ],
            'set alt to twitter:image' => [
                [
                    'property' => 'twitter:image',
                    'content' => '/path/to/image',
                    'subProperties' => ['alt' => ['Alternative title']]
                ],
                [
                    [
                        'content' => '/path/to/image',
                        'subProperties' => [
                            'alt' => ['Alternative title']
                        ]
                    ]
                ],
                '<meta name="twitter:image" content="/path/to/image" />' . PHP_EOL .
                '<meta name="twitter:image:alt" content="Alternative title" />'
            ]
        ];
    }

    /**
     * @test
     */
    public function checkIfAddingOnlySubPropertyAndNoMainPropertyIsReturningException()
    {
        $manager = new TwitterCardMetaTagManager();

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
                'property' => 'twitter:title',
                'content' => 'This is a title',
                'subProperties' => [],
                'replace' => false,
                'type' => ''
            ],
            [
                'property' => 'twitter:image',
                'content' => '/path/to/image',
                'subProperties' => [
                    'width' => 400
                ],
                'replace' => false,
                'type' => ''
            ],
            [
                'property' => 'twitter:title',
                'content' => 'This is the new title',
                'subProperties' => [],
                'replace' => true,
                'type' => ''
            ],
            [
                'property' => 'twitter:image',
                'content' => '/path/to/image2',
                'subProperties' => [],
                'replace' => false,
                'type' => ''
            ],
        ];

        $manager = new TwitterCardMetaTagManager();
        foreach ($properties as $property) {
            $manager->addProperty(
                $property['property'],
                $property['content'],
                $property['subProperties'],
                $property['replace'],
                $property['type']
            );
        }

        $expected = '<meta name="twitter:image" content="/path/to/image" />' . PHP_EOL .
            '<meta name="twitter:title" content="This is the new title" />';

        self::assertEquals($expected, $manager->renderAllProperties());
    }

    /**
     * @test
     */
    public function checkIfRemovePropertyReallyRemovesProperty()
    {
        $manager = new TwitterCardMetaTagManager();
        $manager->addProperty('twitter:title', 'Title');
        self::assertEquals([['content' => 'Title', 'subProperties' => []]], $manager->getProperty('twitter:title'));

        $manager->removeProperty('twitter:title');
        self::assertEquals([], $manager->getProperty('twitter:title'));

        $manager->addProperty('twitter:title', 'Title');
        $manager->addProperty('twitter:description', 'Description');

        $manager->removeAllProperties();

        self::assertEquals([], $manager->getProperty('twitter:title'));
        self::assertEquals([], $manager->getProperty('twitter:description'));
    }
}
