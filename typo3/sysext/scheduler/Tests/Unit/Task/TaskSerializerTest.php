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

namespace TYPO3\CMS\Scheduler\Tests\Unit\Task;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Log\Writer\NullWriter;
use TYPO3\CMS\Scheduler\Exception\InvalidTaskException;
use TYPO3\CMS\Scheduler\Task\TaskSerializer;
use TYPO3\CMS\Scheduler\Tests\Unit\Task\Fixtures\TestTask;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TaskSerializerTest extends UnitTestCase
{
    public static function dataIsDeserializedDataProvider(): array
    {
        $testTaskWithString = new TestTask();
        $testTaskWithString->any = [bin2hex(random_bytes(10))];

        $testTaskWithStdClass = new TestTask();
        $testTaskWithStdClass->any = [new \stdClass()];

        return [
            'TestTask with string' => [
                serialize($testTaskWithString),
                $testTaskWithString,
            ],
            'TestTask with stdClass' => [
                serialize($testTaskWithStdClass),
                $testTaskWithStdClass,
            ],
        ];
    }

    #[DataProvider('dataIsDeserializedDataProvider')]
    #[Test]
    public function dataIsDeserialized(string $data, $expectation): void
    {
        $taskSerializer = new TaskSerializer();
        self::assertEquals($expectation, $taskSerializer->deserialize($data));
    }

    public static function deserializationThrowsExceptionDataProvider(): array
    {
        // as reported in https://forge.typo3.org/issues/92466 & https://forge.typo3.org/issues/91766
        $testTaskWithNullWriter = new TestTask();
        $testTaskWithNullWriter->any = [new NullWriter()];

        $testTaskWithSerializationTypeError = new TestTask();
        $serializedTestTaskWithSerializationTypeError = serialize($testTaskWithSerializationTypeError);
        // Doing a string replace here because due to special escape characters,
        // we cannot do a raw copy+paste as a fixture here.
        // The following will set the serialized data for the
        // property 'any' from an 'array' to a 'string', which will cause
        // a type error on unserialization.
        $serializedTestTaskWithSerializationTypeError = str_replace(
            's:3:"any";a:0:{}',
            's:3:"any";s:7:"invalid";',
            $serializedTestTaskWithSerializationTypeError
        );

        return [
            'blank' => [
                '',
                1642956282,
            ],
            'invalid' => [
                '{}',
                1642956282,
            ],
            'invalid task' => [
                'O:29:"TYPO3\CMS\Testing\InvalidTask":1:{s:5:"value";s:5:"value";}',
                1642954501,
            ],
            'invalid root type' => [
                'a:1:{i:0;O:29:"TYPO3\CMS\Testing\InvalidTask":1:{s:5:"value";s:5:"value";}}',
                1642954501,
            ],
            'Provoking TypeError' => [
                $serializedTestTaskWithSerializationTypeError,
                1740514197,
            ],
            'TestTask with NullWriter' => [
                serialize($testTaskWithNullWriter),
                1642938352,
            ],
        ];
    }

    #[DataProvider('deserializationThrowsExceptionDataProvider')]
    #[Test]
    public function deserializationThrowsException(string $data, int $exceptionCode): void
    {
        $this->expectException(InvalidTaskException::class);
        $this->expectExceptionCode($exceptionCode);
        $taskSerializer = new TaskSerializer();
        $taskSerializer->deserialize($data);
    }

    public static function classNameIsResolvedDataProvider(): array
    {
        $missingTaskData = 'O:29:"TYPO3\CMS\Testing\MissingTask":0:{}';
        $missingTask = unserialize($missingTaskData, ['allowed_classes' => false]);

        return [
            'stdClass' => [
                new \stdClass(),
                \stdClass::class,
            ],
            'MissingTask' => [
                $missingTask,
                'TYPO3\CMS\Testing\MissingTask',
            ],
        ];
    }

    #[DataProvider('classNameIsResolvedDataProvider')]
    #[Test]
    public function classNameIsResolved(?object $task, ?string $expectation): void
    {
        $taskSerializer = new TaskSerializer();
        self::assertSame($expectation, $taskSerializer->resolveClassName($task));
    }

    public static function classNameIsExtractedDataProvider(): array
    {
        return [
            'from object serialization' => [
                'O:29:"TYPO3\CMS\Testing\MissingTask":0:{}',
                'TYPO3\CMS\Testing\MissingTask',
            ],
            'from (invalid) array serialization #1' => [
                'a:1:{i:0;O:29:"TYPO3\CMS\Testing\MissingTask":0:{}}',
                null,
            ],
            'from (invalid) array serialization #2' => [
                'a:1:{s:4:"I-am";s:8:"an-array";}',
                null,
            ],
        ];
    }

    #[DataProvider('classNameIsExtractedDataProvider')]
    #[Test]
    public function classNameIsExtracted(string $serializedTask, ?string $expectation): void
    {
        $taskSerializer = new TaskSerializer();
        self::assertSame($expectation, $taskSerializer->extractClassName($serializedTask));
    }
}
