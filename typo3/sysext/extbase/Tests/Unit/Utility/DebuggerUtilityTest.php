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

namespace TYPO3\CMS\Extbase\Tests\Unit\Utility;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Tests\Fixture\DummyClass;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DebuggerUtilityTest extends UnitTestCase
{
    /**
     * @test
     */
    public function debuggerRewindsInstancesOfIterator(): void
    {
        $objectStorage = $this->getMockBuilder(ObjectStorage::class)
            ->addMethods(['dummy'])
            ->getMock();
        for ($i = 0; $i < 5; $i++) {
            $obj = new \stdClass();
            $obj->property = $i;
            $objectStorage->attach($obj);
        }
        DebuggerUtility::var_dump($objectStorage, null, 8, true, false, true);
        self::assertTrue($objectStorage->valid());
    }

    /**
     * @test
     */
    public function debuggerDoesNotRewindInstancesOfGenerator(): void
    {
        $generator = (static function () {
            yield 1;
            yield 2;
            yield 3;
        })();
        $result = DebuggerUtility::var_dump($generator, null, 8, true, false, true);
        self::assertStringContainsString('Generator', $result);
    }

    /**
     * @test
     */
    public function varDumpShowsPropertiesOfStdClassObjects(): void
    {
        $testObject = new \stdClass();
        $testObject->foo = 'bar';
        $result = DebuggerUtility::var_dump($testObject, null, 8, true, false, true);
        self::assertMatchesRegularExpression('/foo.*bar/', $result);
    }

    /**
     * @test
     */
    public function varDumpHandlesVariadicArguments(): void
    {
        $result = DebuggerUtility::var_dump(static function (...$args) {
        }, null, 8, true, false, true);
        self::assertStringContainsString('function (...$args)', $result);
    }

    /**
     * @test
     */
    public function varDumpRespectsBlacklistedProperties(): void
    {
        $testClass = new \stdClass();
        $testClass->secretData = 'I like cucumber.';
        $testClass->notSoSecretData = 'I like burger.';

        $result = DebuggerUtility::var_dump($testClass, null, 8, true, false, true, null, ['secretData']);
        self::assertStringNotContainsString($testClass->secretData, $result);
    }

    /**
     * @test
     */
    public function varDumpRespectsBlacklistedClasses(): void
    {
        $testClass = new \stdClass();
        $testClass->data = 'I like burger.';

        $result = DebuggerUtility::var_dump($testClass, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringNotContainsString($testClass->data, $result);
    }

    /**
     * @test
     */
    public function varDumpShowsDumpOfDateTime(): void
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2018-11-26 09:27:28', new \DateTimeZone('UTC'));

        $result = DebuggerUtility::var_dump($date, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('2018-11-26T09:27:28', $result);
    }

    /**
     * @test
     */
    public function varDumpShowsDumpOfDateTimeImmutable(): void
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2018-11-26 09:27:28', new \DateTimeZone('UTC'));

        $result = DebuggerUtility::var_dump($date, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('2018-11-26T09:27:28', $result);
    }

    /**
     * @test
     */
    public function varDumpShowsDumpOfClosureWithArrayParameterType(): void
    {
        $closure = (static function (array $array) {});

        $result = DebuggerUtility::var_dump($closure, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('function (array $array)', $result);
    }

    /**
     * @test
     */
    public function varDumpShowsDumpOfClosureWithNullableArrayParameterTypeShowingOnlyArray(): void
    {
        $closure = (static function (?array $array) {});

        $result = DebuggerUtility::var_dump($closure, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('function (array $array)', $result);
    }

    /**
     * @test
     */
    public function varDumpShowsDumpOfClosureWithDummyClassParameterType(): void
    {
        $closure = (static function (DummyClass $class) {});

        $result = DebuggerUtility::var_dump($closure, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('function (TYPO3\CMS\Extbase\Tests\Fixture\DummyClass $class)', $result);
    }

    /**
     * @test
     */
    public function varDumpShowsDumpOfClosureWithIntClassParameterType(): void
    {
        $closure = (static function (int $int) {});

        $result = DebuggerUtility::var_dump($closure, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('function ($int)', $result);
    }

    /**
     * @test
     */
    public function varDumpShowsDumpOfClosureWithStringClassParameterType(): void
    {
        $closure = (static function (string $string) {});

        $result = DebuggerUtility::var_dump($closure, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('function ($string)', $result);
    }

    /**
     * @test
     */
    public function varDumpShowsDumpOfClosureWithoutClassParameterType(): void
    {
        $closure = (static function ($typeless) {});

        $result = DebuggerUtility::var_dump($closure, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('function ($typeless)', $result);
    }

    /**
     * @test
     */
    public function varDumpShowsUninitializedVariable(): void
    {
        $class = new class () {
            protected \stdClass $test;
        };

        $result = DebuggerUtility::var_dump($class, null, 8, true, false, true);
        self::assertStringContainsString('test => protected uninitialized', $result);
    }
}
