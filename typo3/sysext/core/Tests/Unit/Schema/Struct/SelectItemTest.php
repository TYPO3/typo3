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

namespace TYPO3\CMS\Core\Tests\Unit\Schema\Struct;

use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SelectItemTest extends UnitTestCase
{
    public function selectionItemCanBeConstructedFromTcaItemDataProvider(): iterable
    {
        yield 'only required fields' => [
            ['label' => 'foo', 'value' => 'bar'],
            'select',
            ['label' => 'foo', 'value' => 'bar', 'icon' => null, 'group' => null, 'description' => null],
        ];

        yield 'all fields' => [
            ['label' => 'foo', 'value' => 'bar', 'icon' => 'myIcon', 'group' => 'myGroup', 'description' => 'myDescription'],
            'select',
            ['label' => 'foo', 'value' => 'bar', 'icon' => 'myIcon', 'group' => 'myGroup', 'description' => 'myDescription'],
        ];

        yield 'integer as value' => [
            ['label' => 'foo', 'value' => 1, 'icon' => 'myIcon', 'group' => 'myGroup', 'description' => 'myDescription'],
            'select',
            ['label' => 'foo', 'value' => 1, 'icon' => 'myIcon', 'group' => 'myGroup', 'description' => 'myDescription'],
        ];

        yield 'checkbox fields' => [
            ['label' => 'foo', 'invertStateDisplay' => true, 'iconIdentifierChecked' => 'foo1', 'iconIdentifierUnchecked' => 'foo2', 'labelChecked' => 'foo3', 'labelUnchecked' => 'foo4'],
            'check',
            ['label' => 'foo', 'invertStateDisplay' => true, 'iconIdentifierChecked' => 'foo1', 'iconIdentifierUnchecked' => 'foo2', 'labelChecked' => 'foo3', 'labelUnchecked' => 'foo4'],
        ];

        yield 'radio fields' => [
            ['label' => 'foo', 'value' => 'bar'],
            'radio',
            ['label' => 'foo', 'value' => 'bar'],
        ];

        yield 'legacy indexed array keys' => [
            ['foo', 'bar', 'myIcon', 'myGroup', 'myDescription'],
            'select',
            ['label' => 'foo', 'value' => 'bar', 'icon' => 'myIcon', 'group' => 'myGroup', 'description' => 'myDescription'],
        ];
    }

    /**
     * @test
     * @dataProvider selectionItemCanBeConstructedFromTcaItemDataProvider
     */
    public function selectionItemCanBeConstructedFromTcaItem(array $item, string $type, array $expected): void
    {
        $selectionItem = SelectItem::fromTcaItemArray($item, $type);

        self::assertSame($expected, $selectionItem->toArray());
    }

    /**
     * @test
     */
    public function dividerValueCanBeIdentified(): void
    {
        $item = ['label' => 'foo', 'value' => '--div--'];
        $selectionItem = SelectItem::fromTcaItemArray($item);

        self::assertTrue($selectionItem->isDivider());
    }

    public function notSetValuesCanBeIdentifiedDataProvider(): iterable
    {
        yield 'only required fields' => [
            ['label' => 'foo', 'value' => 'bar'],
            ['icon' => false, 'group' => false, 'description' => false],
        ];

        yield 'all fields' => [
            ['label' => 'foo', 'value' => 'bar', 'icon' => 'myIcon', 'group' => 'myGroup', 'description' => 'myDescription'],
            ['icon' => true, 'group' => true, 'description' => true],
        ];
    }

    /**
     * @test
     * @dataProvider notSetValuesCanBeIdentifiedDataProvider
     */
    public function notSetValuesCanBeIdentified(array $item, array $expected): void
    {
        $selectionItem = SelectItem::fromTcaItemArray($item);

        self::assertSame($expected['icon'], $selectionItem->hasIcon());
        self::assertSame($expected['group'], $selectionItem->hasGroup());
        self::assertSame($expected['description'], $selectionItem->hasDescription());
    }

    /**
     * @test
     */
    public function canBeAccessedAsAnArray(): void
    {
        $selectionItem = new SelectItem(
            type: 'select',
            label: 'foo',
            value: 'bar',
            icon: 'myIcon',
            group: 'myGroup',
            description: 'my description',
        );

        self::assertSame('foo', $selectionItem['label']);
        self::assertSame('bar', $selectionItem['value']);
        self::assertSame('myIcon', $selectionItem['icon']);
        self::assertSame('myGroup', $selectionItem['group']);
        self::assertSame('my description', $selectionItem['description']);
    }

    /**
     * @test
     */
    public function canBeAccessedAsAnArrayWithLegacyIndexedKeys(): void
    {
        $selectionItem = new SelectItem(
            type: 'select',
            label: 'foo',
            value: 'bar',
            icon: 'myIcon',
            group: 'myGroup',
            description: 'my description',
        );

        self::assertSame('foo', $selectionItem[0]);
        self::assertSame('bar', $selectionItem[1]);
        self::assertSame('myIcon', $selectionItem[2]);
        self::assertSame('myGroup', $selectionItem[3]);
        self::assertSame('my description', $selectionItem[4]);
    }

    /**
     * @test
     */
    public function canBeManipulatedLikeAnArray(): void
    {
        $selectionItem = new SelectItem(
            type: 'select',
            label: 'foo',
            value: 'bar',
        );

        $selectionItem['label'] = 'oof';
        $selectionItem['value'] = 'rab';
        $selectionItem['icon'] = 'myIcon';
        $selectionItem['group'] = 'myGroup';
        $selectionItem['description'] = 'my description';

        self::assertSame('oof', $selectionItem->getLabel());
        self::assertSame('rab', $selectionItem->getValue());
        self::assertSame('myIcon', $selectionItem->getIcon());
        self::assertSame('myGroup', $selectionItem->getGroup());
        self::assertSame('my description', $selectionItem->getDescription());
    }

    /**
     * @test
     */
    public function canBeManipulatedLikeAnArrayWithLegacyIndexedKeys(): void
    {
        $selectionItem = new SelectItem(
            type: 'select',
            label: 'foo',
            value: 'bar',
        );

        $selectionItem[0] = 'oof';
        $selectionItem[1] = 'rab';
        $selectionItem[2] = 'myIcon';
        $selectionItem[3] = 'myGroup';
        $selectionItem[4] = 'my description';

        self::assertSame('oof', $selectionItem->getLabel());
        self::assertSame('rab', $selectionItem->getValue());
        self::assertSame('myIcon', $selectionItem->getIcon());
        self::assertSame('myGroup', $selectionItem->getGroup());
        self::assertSame('my description', $selectionItem->getDescription());
    }

    /**
     * @test
     */
    public function valuesCanBeUnsetWithUnsetFunction(): void
    {
        $selectionItem = new SelectItem(
            type: 'select',
            label: 'foo',
            value: 'bar',
            icon: 'myIcon',
            group: 'myGroup',
            description: 'my description',
        );

        unset($selectionItem['icon']);
        unset($selectionItem['group']);
        unset($selectionItem['description']);

        self::assertNull($selectionItem->getIcon());
        self::assertNull($selectionItem->getGroup());
        self::assertNull($selectionItem->getDescription());
    }

    /**
     * @test
     */
    public function valuesCanBeUnsetWithUnsetFunctionWithLegacyIndexedKeys(): void
    {
        $selectionItem = new SelectItem(
            type: 'select',
            label: 'foo',
            value: 'bar',
            icon: 'myIcon',
            group: 'myGroup',
            description: 'my description',
        );

        unset($selectionItem[2]);
        unset($selectionItem[3]);
        unset($selectionItem[4]);

        self::assertNull($selectionItem->getIcon());
        self::assertNull($selectionItem->getGroup());
        self::assertNull($selectionItem->getDescription());
    }

    /**
     * @test
     */
    public function arrayOffsetsCanBeTestedWithIssetFunction(): void
    {
        $selectionItem = new SelectItem(
            type: 'select',
            label: 'foo',
            value: 'bar',
        );

        self::assertTrue(isset($selectionItem['label']));
        self::assertTrue(isset($selectionItem['value']));
        self::assertFalse(isset($selectionItem['icon']));
        self::assertFalse(isset($selectionItem['group']));
        self::assertFalse(isset($selectionItem['description']));
    }

    /**
     * @test
     */
    public function arrayOffsetsCanBeTestedWithIssetFunctionWithLegacyIndexedKeys(): void
    {
        $selectionItem = new SelectItem(
            type: 'select',
            label: 'foo',
            value: 'bar',
        );

        self::assertTrue(isset($selectionItem[0]));
        self::assertTrue(isset($selectionItem[1]));
        self::assertFalse(isset($selectionItem[2]));
        self::assertFalse(isset($selectionItem[3]));
        self::assertFalse(isset($selectionItem[4]));
    }

    /**
     * @test
     */
    public function canSetCustomValueInArrayLikeFashion(): void
    {
        $selectionItem = new SelectItem(
            type: 'select',
            label: 'foo',
            value: 'bar',
        );

        $selectionItem['custom'] = 'customValue';

        self::assertTrue(isset($selectionItem['custom']));
        self::assertSame('customValue', $selectionItem['custom']);
    }

    /**
     * @test
     */
    public function canUnsetCustomValueInArrayLikeFashion(): void
    {
        $selectionItem = new SelectItem(
            type: 'select',
            label: 'foo',
            value: 'bar',
        );

        $selectionItem['custom'] = 'customValue';
        unset($selectionItem['custom']);

        self::assertFalse(isset($selectionItem['custom']));
        self::assertNull($selectionItem['custom']);
    }
}
