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

namespace TYPO3\CMS\Core\Tests\Unit\Compatibility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PublicPropertyDeprecationTraitTest extends UnitTestCase
{
    /**
     * @var object Test fixture (anonymous class)
     * @see PublicAccessDeprecationTraitTest::setUp()
     */
    protected $fixture;

    /**
     * Setup
     *
     * Creating the test fixture, an anonymous class with different kinds
     * of properties to test access for.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->fixture = new class () {
            use PublicPropertyDeprecationTrait;
            private $deprecatedPublicProperties = [
                'taggedProperty' => 'taggedProperty is deprecated',
                'unsetTaggedProperty' => 'unsetTaggedProperty is deprecated',
            ];

            public $publicProperty = 'publicProperty';

            public $unsetPublicProperty;

            /**
             * @deprecatedPublic
             */
            protected $taggedProperty = 'taggedProperty';

            /**
             * @deprecatedPublic
             */
            protected $unsetTaggedProperty;

            protected $untaggedProperty = 'untaggedProperty';
        };
    }

    /**
     * @return array [[$expected, $property],]
     */
    public static function issetDataProvider(): array
    {
        return [
            'public property' => [true, 'publicProperty'],
            'unset public property' => [false, 'unsetPublicProperty'],
            'tagged property' => [true, 'taggedProperty'],
            'unset tagged property' => [false, 'unsetTaggedProperty'],
            'untagged property' => [false, 'untaggedProperty'],
            'unknown property' => [false, 'unknownProperty'],
        ];
    }

    /**
     * Do NOT remove this test, even though it has IgnoreDeprecations attribute,
     * we're testing the core's deprecation strategy here.
     */
    #[DataProvider('issetDataProvider')]
    #[Test]
    #[IgnoreDeprecations]
    public function issetWorksAsExpected(bool $expected, string $property): void
    {
        self::assertSame($expected, isset($this->fixture->$property));
    }

    /**
     * Do NOT remove this test, even though it has IgnoreDeprecations attribute,
     * we're testing the core's deprecation strategy here.
     */
    #[Test]
    #[IgnoreDeprecations]
    public function unknownPropertyCanBeHandledAsUsual(): void
    {
        // Uses __isset()
        self::assertFalse(isset($this->fixture->unknownProperty));
        // Uses __set()
        $this->fixture->unknownProperty = 23;
        // Don't uses __isset()
        self::assertTrue(isset($this->fixture->unknownProperty));
        // Don't uses __get()
        self::assertSame(23, $this->fixture->unknownProperty);
        // Don't uses __unset()
        unset($this->fixture->unknownProperty);
        // Uses __isset()
        self::assertFalse(isset($this->fixture->unknownProperty));
    }

    #[Test]
    public function publicPropertyCanBeHandledAsUsual(): void
    {
        self::assertFalse(isset($this->fixture->unsetPublicProperty));
        $this->fixture->unsetPublicProperty = 23;
        self::assertTrue(isset($this->fixture->unsetPublicProperty));
        self::assertSame(23, $this->fixture->unsetPublicProperty);
        unset($this->fixture->unsetPublicProperty);
        self::assertFalse(isset($this->fixture->unsetPublicProperty));
    }

    /**
     * Do NOT remove this test, even though it has IgnoreDeprecations attribute,
     * we're testing the core's deprecation strategy here.
     */
    #[Test]
    #[IgnoreDeprecations]
    public function taggedPropertyCanBeHandledLikePublicProperty(): void
    {
        self::assertFalse(isset($this->fixture->unsetTaggedProperty));
        $this->fixture->unsetTaggedProperty = 23;
        self::assertTrue(isset($this->fixture->unsetTaggedProperty));
        self::assertSame(23, $this->fixture->unsetTaggedProperty);
        unset($this->fixture->unsetTaggedProperty);
        self::assertFalse(isset($this->fixture->unsetTaggedProperty));
    }
}
