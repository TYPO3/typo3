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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\FormElements;

use TYPO3\CMS\Form\Domain\Model\FormElements\Section;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test TYPO3\CMS\Form\Domain\Model\FormElements\Section class
 *
 * Class AbstractFormElementTest
 */
class SectionTest extends UnitTestCase
{
    protected static $IDENTIFIER = 'an_id';
    protected static $TYPE = 'a_type';

    /**
     * An instance of section
     * @var Section
     */
    protected $sectionInstance;

    /**
     * @before
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->sectionInstance = new Section(self::$IDENTIFIER, self::$TYPE);
    }

    /**
     * @test
     */
    public function newInstanceHasNoProperties(): void
    {
        self::assertNotNull($this->sectionInstance);
        self::assertCount(0, $this->sectionInstance->getProperties());
    }

    /**
     * @test
     */
    public function setSimpleProperties(): void
    {
        $this->sectionInstance->setProperty('foo', 'bar');
        $this->sectionInstance->setProperty('buz', 'qax');
        $properties = $this->sectionInstance->getProperties();

        self::assertCount(2, $properties, json_encode($properties));
        self::assertTrue(array_key_exists('foo', $properties));
        self::assertEquals('bar', $properties['foo']);
        self::assertTrue(array_key_exists('buz', $properties));
        self::assertEquals('qax', $properties['buz']);
    }

    /**
     * @test
     */
    public function overrideProperties(): void
    {
        $this->sectionInstance->setProperty('foo', 'bar');
        $this->sectionInstance->setProperty('foo', 'buz');

        $properties = $this->sectionInstance->getProperties();
        self::assertEquals(1, \count($properties));
        self::assertTrue(array_key_exists('foo', $properties));
        self::assertEquals('buz', $properties['foo']);
    }

    /**
     * @test
     */
    public function setArrayProperties(): void
    {
        $this->sectionInstance->setProperty('foo', ['bar' => 'baz', 'bla' => 'blubb']);
        $properties = $this->sectionInstance->getProperties();

        self::assertCount(1, $properties);
        self::assertTrue(array_key_exists('foo', $properties));

        //check arrays details
        self::assertTrue(\is_array($properties['foo']));
        self::assertCount(2, $properties['foo']);
        self::assertTrue(array_key_exists('bar', $properties['foo']));
        self::assertEquals('baz', $properties['foo']['bar']);
    }

    /**
     * @test
     */
    public function setPropertyUnsetIfValueIsNull(): void
    {
        $expected = ['foo-1' => ['bar-1' => 'foo-2']];
        $this->sectionInstance->setProperty('foo-1', ['bar-1' => 'foo-2']);
        $this->sectionInstance->setProperty('foo-2', ['bar-2' => 'foo-3']);
        $this->sectionInstance->setProperty('foo-2', null);

        self::assertSame($expected, $this->sectionInstance->getProperties());
    }

    /**
     * @test
     */
    public function setPropertyUnsetIfValueIsArrayWithSomeNullVales(): void
    {
        $expected = [
            'foo-1' => [
                'bar-1' => 'foo-2'
            ],
            'foo-2' => [
                'bar-2' => 'foo-3'
            ]
        ];
        $this->sectionInstance->setProperty('foo-1', ['bar-1' => 'foo-2']);
        $this->sectionInstance->setProperty('foo-2', ['bar-2' => 'foo-3', 'bar-3' => 'foo-4']);
        $this->sectionInstance->setProperty('foo-2', ['bar-3' => null]);

        self::assertSame($expected, $this->sectionInstance->getProperties());
    }

    /**
     * @test
     */
    public function setRenderingOptionSetStringValueIfKeyDoesNotExists(): void
    {
        $expected = ['foo' => 'bar'];
        $this->sectionInstance->setRenderingOption('foo', 'bar');

        self::assertSame($expected, $this->sectionInstance->getRenderingOptions());
    }

    /**
     * @test
     */
    public function setRenderingOptionSetArrayValueIfKeyDoesNotExists(): void
    {
        $expected = ['foo-1' => ['bar' => 'foo-2']];
        $this->sectionInstance->setRenderingOption('foo-1', ['bar' => 'foo-2']);

        self::assertSame($expected, $this->sectionInstance->getRenderingOptions());
    }

    /**
     * @test
     */
    public function setRenderingOptionUnsetIfValueIsNull(): void
    {
        $expected = ['foo-1' => ['bar-1' => 'foo-2']];
        $this->sectionInstance->setRenderingOption('foo-1', ['bar-1' => 'foo-2']);
        $this->sectionInstance->setRenderingOption('foo-2', ['bar-2' => 'foo-3']);
        $this->sectionInstance->setRenderingOption('foo-2', null);

        self::assertSame($expected, $this->sectionInstance->getRenderingOptions());
    }

    /**
     * @test
     */
    public function setRenderingOptionUnsetIfValueIsArrayWithSomeNullVales(): void
    {
        $expected = [
            'foo-1' => [
                'bar-1' => 'foo-2'
            ],
            'foo-2' => [
                'bar-2' => 'foo-3'
            ]
        ];
        $this->sectionInstance->setRenderingOption('foo-1', ['bar-1' => 'foo-2']);
        $this->sectionInstance->setRenderingOption('foo-2', ['bar-2' => 'foo-3', 'bar-3' => 'foo-4']);
        $this->sectionInstance->setRenderingOption('foo-2', ['bar-3' => null]);

        self::assertSame($expected, $this->sectionInstance->getRenderingOptions());
    }

    /**
     * @test
     */
    public function setRenderingOptionAddValueIfValueIsArray(): void
    {
        $expected = [
            'foo-1' => [
                'bar-1' => 'foo-2'
            ],
            'foo-2' => [
                'bar-2' => 'foo-3',
                'bar-3' => 'foo-4'
            ]
        ];
        $this->sectionInstance->setRenderingOption('foo-1', ['bar-1' => 'foo-2']);
        $this->sectionInstance->setRenderingOption('foo-2', ['bar-2' => 'foo-3']);
        $this->sectionInstance->setRenderingOption('foo-2', ['bar-3' => 'foo-4']);

        self::assertSame($expected, $this->sectionInstance->getRenderingOptions());
    }
}
