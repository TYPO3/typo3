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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Service\ResourceConsistencyService;
use TYPO3\CMS\Core\Validation\ResultException;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ResourceConsistencyServiceTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Resource/Fixtures/ProcessedFileTest.jpg' => 'fileadmin/ProcessedFileTest.jpg',
    ];

    private ResourceConsistencyService $subject;
    private \ReflectionMethod $subjectShallValidate;
    private array $items;
    private array $storages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->get(ResourceConsistencyService::class);
        $this->subjectShallValidate = (new \ReflectionObject($this->subject))->getMethod('shallValidate');
        $this->subjectShallValidate->setAccessible(true);

        $this->storages = [
            1 => $this->createMockedStorage(1),
            2 => $this->createMockedStorage(2),
        ];
        $this->items = [
            [
                'storage' => $this->storages[1],
                'resource' => '/image.png',
                'targetFileName' => 'target.png',
            ],
            [
                'storage' => $this->storages[2],
                'resource' => '/image.png',
                'targetFileName' => 'target.png',
            ],
        ];
    }

    #[Test]
    public function validationSucceeds(): void
    {
        $this->expectNotToPerformAssertions();

        $this->subject->validate(
            $this->storages[1],
            $this->instancePath . '/fileadmin/ProcessedFileTest.jpg',
            'ProcessedFileTest.jpg'
        );
    }

    #[Test]
    public function validationFails(): void
    {
        $this->expectException(ResultException::class);
        $this->expectExceptionCode(1747230949);
        $this->expectExceptionMessage('Resource consistency check failed');

        $this->subject->validate(
            $this->storages[1],
            $this->instancePath . '/fileadmin/ProcessedFileTest.jpg',
            'ProcessedFileTest.exe'
        );
    }

    #[Test]
    public function shallValidateConsidersExceptionItems(): void
    {
        $this->subject->addExceptionItem(...$this->items[0]);
        $this->subject->addExceptionItem(...$this->items[1]);
        self::assertFalse($this->subjectShallValidate->invokeArgs($this->subject, array_values($this->items[0])));
        self::assertFalse($this->subjectShallValidate->invokeArgs($this->subject, array_values($this->items[1])));
        // this must be true now, since those items have been "consumed" in the previous invocations
        self::assertTrue($this->subjectShallValidate->invokeArgs($this->subject, array_values($this->items[0])));
        self::assertTrue($this->subjectShallValidate->invokeArgs($this->subject, array_values($this->items[1])));
    }

    private function createMockedStorage(int $uid): ResourceStorage&MockObject
    {
        $mock = $this->getMockBuilder(ResourceStorage::class)
            ->onlyMethods(['getUid'])
            ->disableOriginalConstructor()
            ->getMock();
        $mock->method('getUid')->willReturn($uid);
        return $mock;
    }
}
