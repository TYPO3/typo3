<?php

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
    public function debuggerRewindsInstancesOfIterator()
    {
        /** @var $objectStorage \TYPO3\CMS\Extbase\Persistence\ObjectStorage */
        $objectStorage = $this->getMockBuilder(ObjectStorage::class)
            ->setMethods(['dummy'])
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
    public function debuggerDoesNotRewindInstancesOfGenerator()
    {
        $generator = (function () {
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
    public function varDumpShowsPropertiesOfStdClassObjects()
    {
        $testObject = new \stdClass();
        $testObject->foo = 'bar';
        $result = DebuggerUtility::var_dump($testObject, null, 8, true, false, true);
        self::assertRegExp('/foo.*bar/', $result);
    }

    /**
     * @test
     */
    public function varDumpHandlesVariadicArguments()
    {
        $result = DebuggerUtility::var_dump(function (...$args) {
        }, null, 8, true, false, true);
        self::assertStringContainsString('function (...$args)', $result);
    }

    /**
     * @test
     */
    public function varDumpRespectsBlacklistedProperties()
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
    public function varDumpRespectsBlacklistedClasses()
    {
        $testClass = new \stdClass();
        $testClass->data = 'I like burger.';

        $result = DebuggerUtility::var_dump($testClass, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringNotContainsString($testClass->data, $result);
    }

    /**
     * @test
     */
    public function varDumpShowsDumpOfDateTime()
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2018-11-26 09:27:28', new \DateTimeZone('UTC'));

        $result = DebuggerUtility::var_dump($date, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('2018-11-26T09:27:28', $result);
    }

    /**
     * @test
     */
    public function varDumpShowsDumpOfDateTimeImmutable()
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2018-11-26 09:27:28', new \DateTimeZone('UTC'));

        $result = DebuggerUtility::var_dump($date, null, 8, true, false, true, [\stdClass::class]);
        self::assertStringContainsString('2018-11-26T09:27:28', $result);
    }
}
