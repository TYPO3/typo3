<?php

declare(strict_types=1);

namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection;

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

use TYPO3\CMS\Extbase\Reflection\Exception\UnknownClassException;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithInvalidTypeHint;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 * @see test for reflection
 * @link second test for reflection
 * @link second test for reflection with second value
 */
class ReflectionServiceTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getClassSchemaThrowsExceptionIfClassIsNotFound(): void
    {
        $this->expectException(UnknownClassException::class);
        $this->expectExceptionCode(1278450972);
        $this->expectExceptionMessage('Class Foo\Bar\Not\Existing does not exist. Reflection failed.');
        $reflectionService = new ReflectionService();
        $reflectionService->getClassSchema('Foo\Bar\Not\Existing');
    }

    /**
     * @test
     */
    public function getClassSchemaThrowsExceptionIfTypeHintedClassWasNotFound(): void
    {
        $this->expectException(UnknownClassException::class);
        $this->expectExceptionCode(1278450972);
        $this->expectExceptionMessage('Class Foo\Bar\Not\Found does not exist. Reflection failed.');
        $reflectionService = new ReflectionService();
        $reflectionService->getClassSchema(DummyClassWithInvalidTypeHint::class);
    }
}
