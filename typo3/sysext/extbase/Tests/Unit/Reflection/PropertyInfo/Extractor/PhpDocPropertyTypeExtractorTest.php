<?php
declare(strict_types = 1);

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

namespace Symfony\Component\PropertyInfo\Tests\PhpDocExtractor;

use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Type;
use TYPO3\CMS\Extbase\Reflection\PropertyInfo\Extractor\PhpDocPropertyTypeExtractor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PhpDocPropertyTypeExtractorTest extends UnitTestCase
{
    /**
     * @var PhpDocExtractor
     */
    private static $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        static::$extractor = new PhpDocPropertyTypeExtractor();
    }

    /**
     * @dataProvider typesProvider
     *
     * @param string $property
     * @param array|null $type
     * @param string|null $shortDescription
     * @param string|null $longDescription
     */
    public function testExtract(string $property, ?array $type, ?string $shortDescription, ?string $longDescription): void
    {
        $this->assertEquals($type, static::$extractor->getTypes('Symfony\Component\PropertyInfo\Tests\Fixtures\Dummy', $property));
    }

    /**
     * @dataProvider typesWithNoPrefixesProvider
     *
     * @param string $property
     * @param array|null $type
     */
    public function testExtractTypesWithNoPrefixes(string $property, array $type = null): void
    {
        $noPrefixExtractor = new PhpDocExtractor(null, [], [], []);

        $this->assertEquals($type, $noPrefixExtractor->getTypes('Symfony\Component\PropertyInfo\Tests\Fixtures\Dummy', $property));
    }

    /**
     * @return array
     */
    public function typesProvider(): array
    {
        return [
            ['foo', null, 'Short description.', 'Long description.'],
            ['bar', [new Type(Type::BUILTIN_TYPE_STRING)], 'This is bar', null],
            ['baz', [new Type(Type::BUILTIN_TYPE_INT)], 'Should be used.', null],
            ['foo2', [new Type(Type::BUILTIN_TYPE_FLOAT)], null, null],
            ['foo3', [new Type(Type::BUILTIN_TYPE_CALLABLE)], null, null],
            ['foo4', [new Type(Type::BUILTIN_TYPE_NULL)], null, null],
            ['foo5', null, null, null],
            [
                'files',
                [
                    new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'SplFileInfo')),
                    new Type(Type::BUILTIN_TYPE_RESOURCE),
                ],
                null,
                null,
            ],
            ['bal', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTime')], null, null],
            ['parent', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Symfony\Component\PropertyInfo\Tests\Fixtures\ParentDummy')], null, null],
            ['collection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTime'))], null, null],
            ['nestedCollection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING, false)))], null, null],
            ['mixedCollection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, null, null)], null, null],
            ['g', [new Type(Type::BUILTIN_TYPE_ARRAY, true, null, true)], 'Nullable array.', null],
            ['h', [new Type(Type::BUILTIN_TYPE_STRING, true)], null, null],
            ['i', [new Type(Type::BUILTIN_TYPE_STRING, true), new Type(Type::BUILTIN_TYPE_INT, true)], null, null],
            ['j', [new Type(Type::BUILTIN_TYPE_OBJECT, true, 'DateTime')], null, null],
            ['donotexist', null, null, null],
            ['staticGetter', null, null, null],
            ['staticSetter', null, null, null],
            ['emptyVar', null, null, null],
        ];
    }

    /**
     * @dataProvider provideCollectionTypes
     *
     * @param string $property
     * @param array|null $type
     * @param string|null $shortDescription
     * @param string|null $longDescription
     */
    public function testExtractCollection(string $property, array $type, ?string $shortDescription, ?string $longDescription): void
    {
        if (!class_exists(Collection::class)) {
            $this->markTestSkipped('Collections are not implemented in current phpdocumentor/type-resolver version');
        }

        $this->testExtract($property, $type, $shortDescription, $longDescription);
    }

    /**
     * @return array
     */
    public function provideCollectionTypes(): array
    {
        return [
            ['iteratorCollection', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Iterator', true, null, new Type(Type::BUILTIN_TYPE_STRING))], null, null],
            ['iteratorCollectionWithKey', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Iterator', true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING))], null, null],
            [
                'nestedIterators',
                [new Type(
                    Type::BUILTIN_TYPE_OBJECT,
                    false,
                    'Iterator',
                    true,
                    new Type(Type::BUILTIN_TYPE_INT),
                    new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Iterator', true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING))
                )],
                null,
                null,
            ],
        ];
    }

    /**
     * @dataProvider typesWithCustomPrefixesProvider
     *
     * @param string $property
     * @param array|null $type
     */
    public function testExtractTypesWithCustomPrefixes(string $property, array $type = null): void
    {
        $customExtractor = new PhpDocExtractor(null, ['add', 'remove'], ['is', 'can']);

        $this->assertEquals($type, $customExtractor->getTypes('Symfony\Component\PropertyInfo\Tests\Fixtures\Dummy', $property));
    }

    /**
     * @return array
     */
    public function typesWithCustomPrefixesProvider(): array
    {
        return [
            ['foo', null, 'Short description.', 'Long description.'],
            ['bar', [new Type(Type::BUILTIN_TYPE_STRING)], 'This is bar', null],
            ['baz', [new Type(Type::BUILTIN_TYPE_INT)], 'Should be used.', null],
            ['foo2', [new Type(Type::BUILTIN_TYPE_FLOAT)], null, null],
            ['foo3', [new Type(Type::BUILTIN_TYPE_CALLABLE)], null, null],
            ['foo4', [new Type(Type::BUILTIN_TYPE_NULL)], null, null],
            ['foo5', null, null, null],
            [
                'files',
                [
                    new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'SplFileInfo')),
                    new Type(Type::BUILTIN_TYPE_RESOURCE),
                ],
                null,
                null,
            ],
            ['bal', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTime')], null, null],
            ['parent', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Symfony\Component\PropertyInfo\Tests\Fixtures\ParentDummy')], null, null],
            ['collection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTime'))], null, null],
            ['nestedCollection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING, false)))], null, null],
            ['mixedCollection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, null, null)], null, null],
            ['a', null, 'A.', null],
            ['b', null, 'B.', null],
            ['c', [new Type(Type::BUILTIN_TYPE_BOOL, true)], null, null],
            ['d', [new Type(Type::BUILTIN_TYPE_BOOL)], null, null],
            ['e', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_RESOURCE))], null, null],
            ['f', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTime'))], null, null],
            ['g', [new Type(Type::BUILTIN_TYPE_ARRAY, true, null, true)], 'Nullable array.', null],
            ['h', [new Type(Type::BUILTIN_TYPE_STRING, true)], null, null],
            ['i', [new Type(Type::BUILTIN_TYPE_STRING, true), new Type(Type::BUILTIN_TYPE_INT, true)], null, null],
            ['j', [new Type(Type::BUILTIN_TYPE_OBJECT, true, 'DateTime')], null, null],
            ['donotexist', null, null, null],
            ['staticGetter', null, null, null],
            ['staticSetter', null, null, null],
        ];
    }

    /**
     * @return array
     */
    public function typesWithNoPrefixesProvider(): array
    {
        return [
            ['foo', null, 'Short description.', 'Long description.'],
            ['bar', [new Type(Type::BUILTIN_TYPE_STRING)], 'This is bar', null],
            ['baz', [new Type(Type::BUILTIN_TYPE_INT)], 'Should be used.', null],
            ['foo2', [new Type(Type::BUILTIN_TYPE_FLOAT)], null, null],
            ['foo3', [new Type(Type::BUILTIN_TYPE_CALLABLE)], null, null],
            ['foo4', [new Type(Type::BUILTIN_TYPE_NULL)], null, null],
            ['foo5', null, null, null],
            [
                'files',
                [
                    new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'SplFileInfo')),
                    new Type(Type::BUILTIN_TYPE_RESOURCE),
                ],
                null,
                null,
            ],
            ['bal', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTime')], null, null],
            ['parent', [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Symfony\Component\PropertyInfo\Tests\Fixtures\ParentDummy')], null, null],
            ['collection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTime'))], null, null],
            ['nestedCollection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING, false)))], null, null],
            ['mixedCollection', [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, null, null)], null, null],
            ['a', null, 'A.', null],
            ['b', null, 'B.', null],
            ['c', null, null, null],
            ['d', null, null, null],
            ['e', null, null, null],
            ['f', null, null, null],
            ['g', [new Type(Type::BUILTIN_TYPE_ARRAY, true, null, true)], 'Nullable array.', null],
            ['h', [new Type(Type::BUILTIN_TYPE_STRING, true)], null, null],
            ['i', [new Type(Type::BUILTIN_TYPE_STRING, true), new Type(Type::BUILTIN_TYPE_INT, true)], null, null],
            ['j', [new Type(Type::BUILTIN_TYPE_OBJECT, true, 'DateTime')], null, null],
            ['donotexist', null, null, null],
            ['staticGetter', null, null, null],
            ['staticSetter', null, null, null],
        ];
    }

    /**
     * @return array
     */
    public function dockBlockFallbackTypesProvider(): array
    {
        return [
            'pub' => [
                'pub', [new Type(Type::BUILTIN_TYPE_STRING)],
            ]
        ];
    }

    /**
     * @dataProvider dockBlockFallbackTypesProvider
     *
     * @param string $property
     * @param array $types
     */
    public function testDocBlockFallback(string $property, array $types): void
    {
        $this->assertEquals($types, static::$extractor->getTypes('Symfony\Component\PropertyInfo\Tests\Fixtures\DockBlockFallback', $property));
    }
}
