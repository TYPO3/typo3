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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Tests\Unit\Utility\Fixtures\DebuggerUtilityAccessibleProxy;
use TYPO3\CMS\Extbase\Tests\Unit\Utility\Fixtures\DummyClass;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DebuggerUtilityTest extends UnitTestCase
{
    #[Test]
    public function debuggerRewindsInstancesOfIterator(): void
    {
        $objectStorage = $this->getMockBuilder(ObjectStorage::class)->onlyMethods([])->getMock();
        for ($i = 0; $i < 5; $i++) {
            $obj = new \stdClass();
            $obj->property = $i;
            $objectStorage->attach($obj);
        }
        DebuggerUtility::var_dump($objectStorage, null, 8, true, false, true);
        self::assertTrue($objectStorage->valid());
    }

    #[Test]
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

    #[Test]
    public function varDumpShowsPropertiesOfStdClassObjects(): void
    {
        $testObject = new \stdClass();
        $testObject->foo = 'bar';
        $result = DebuggerUtility::var_dump($testObject, null, 8, true, false, true);
        self::assertMatchesRegularExpression('/foo.*bar/', $result);
    }

    #[Test]
    public function varDumpHandlesVariadicArguments(): void
    {
        $result = DebuggerUtility::var_dump(static function (...$args) {}, null, 8, true, false, true);
        self::assertStringContainsString('function (...$args)', $result);
    }

    #[Test]
    public function varDumpRespectsBlacklistedProperties(): void
    {
        $testClass = new \stdClass();
        $testClass->secretData = 'I like cucumber.';
        $testClass->notSoSecretData = 'I like burger.';

        $result = DebuggerUtility::var_dump($testClass, null, 8, true, false, true, null, ['secretData']);
        self::assertStringNotContainsString($testClass->secretData, $result);
    }

    #[Test]
    public function varDumpRespectsBlacklistedClasses(): void
    {
        $testClass = new \stdClass();
        $testClass->data = 'I like burger.';

        $result = DebuggerUtility::var_dump($testClass, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringNotContainsString($testClass->data, $result);
    }

    #[Test]
    public function varDumpShowsDumpOfDateTime(): void
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2018-11-26 09:27:28', new \DateTimeZone('UTC'));

        $result = DebuggerUtility::var_dump($date, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('2018-11-26T09:27:28', $result);
    }

    #[Test]
    public function varDumpShowsDumpOfDateTimeImmutable(): void
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2018-11-26 09:27:28', new \DateTimeZone('UTC'));

        $result = DebuggerUtility::var_dump($date, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('2018-11-26T09:27:28', $result);
    }

    #[Test]
    public function varDumpShowsDumpOfClosureWithArrayParameterType(): void
    {
        $closure = (static function (array $array) {});

        $result = DebuggerUtility::var_dump($closure, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('function (array $array)', $result);
    }

    #[Test]
    public function varDumpShowsDumpOfClosureWithNullableArrayParameterTypeShowingOnlyArray(): void
    {
        $closure = (static function (?array $array) {});

        $result = DebuggerUtility::var_dump($closure, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('function (array $array)', $result);
    }

    #[Test]
    public function varDumpShowsDumpOfClosureWithDummyClassParameterType(): void
    {
        $closure = (static function (DummyClass $class) {});

        $result = DebuggerUtility::var_dump($closure, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('function (TYPO3\CMS\Extbase\Tests\Unit\Utility\Fixtures\DummyClass $class)', $result);
    }

    #[Test]
    public function varDumpShowsDumpOfClosureWithIntClassParameterType(): void
    {
        $closure = (static function (int $int) {});

        $result = DebuggerUtility::var_dump($closure, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('function ($int)', $result);
    }

    #[Test]
    public function varDumpShowsDumpOfClosureWithStringClassParameterType(): void
    {
        $closure = (static function (string $string) {});

        $result = DebuggerUtility::var_dump($closure, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('function ($string)', $result);
    }

    #[Test]
    public function varDumpShowsDumpOfClosureWithoutClassParameterType(): void
    {
        $closure = (static function ($typeless) {});

        $result = DebuggerUtility::var_dump($closure, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('function ($typeless)', $result);
    }

    #[Test]
    public function varDumpShowsUninitializedVariable(): void
    {
        $class = new class () {
            protected \stdClass $test;
        };

        $result = DebuggerUtility::var_dump($class, null, 8, true, false, true);
        self::assertStringContainsString('test => protected uninitialized', $result);
    }

    #[Test]
    public function varDumpUsesNonceValue(): void
    {
        DebuggerUtilityAccessibleProxy::setStylesheetEchoed(false);
        $class = new class () {
            protected \stdClass $test;
        };
        $result = DebuggerUtilityAccessibleProxy::var_dump($class, null, 8, false, false, true);
        self::assertTrue(DebuggerUtilityAccessibleProxy::getStylesheetEchoed());
        self::assertMatchesRegularExpression('#<style nonce="[^"]+">[^<]+</style>#m', $result);
    }

    #[Test]
    public function varDumpIsSimpleUTF8Aware(): void
    {
        DebuggerUtilityAccessibleProxy::setStylesheetEchoed(true);
        $reallyLongInputWithUTF8 = str_repeat('ÄÖÜ', 669);
        // Wraps at 76 chars, limit of 2000.
        $resultPlaintext = DebuggerUtility::var_dump($reallyLongInputWithUTF8, null, 8, true, false, true);
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Utf8OutputSimple.expected.txt',
            $resultPlaintext,
            'Plaintext'
        );

        $resultHtml = DebuggerUtility::var_dump($reallyLongInputWithUTF8, null, 8, false, false, true);
        // Note: trim()ing needed because otherwise trailing whitespace from a file is cut by IDE optimizations and would not match
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Utf8OutputSimple.expected.html',
            trim($resultHtml) . "\n",
            'HTML'
        );
        DebuggerUtilityAccessibleProxy::setStylesheetEchoed(false);
    }

    #[Test]
    public function varDumpIsComplexUTF8Aware(): void
    {
        DebuggerUtilityAccessibleProxy::setStylesheetEchoed(true);
        $reallyLongInputWithUTF8 = str_repeat('ÄÖÜ😂👩‍👩‍👦‍👦', 337);
        // Funny sidenote. Splitting this family-emoji results in code-points:👩‍|👦|👦|👩‍👩‍👦‍
        // This is part of the expectation to ensure proper splitting!
        // Wraps at 76 chars, limit of 2000.
        $resultPlaintext = DebuggerUtility::var_dump($reallyLongInputWithUTF8, null, 8, true, false, true);

        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Utf8OutputComplex.expected.txt',
            $resultPlaintext,
            'Plaintext'
        );

        $resultHtml = DebuggerUtility::var_dump($reallyLongInputWithUTF8, null, 8, false, false, true);
        // Note: trim()ing needed because otherwise trailing whitespace from a file is cut by IDE optimizations and would not match
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Utf8OutputComplex.expected.html',
            trim($resultHtml) . "\n",
            'HTML'
        );
        DebuggerUtilityAccessibleProxy::setStylesheetEchoed(false);
    }
}
