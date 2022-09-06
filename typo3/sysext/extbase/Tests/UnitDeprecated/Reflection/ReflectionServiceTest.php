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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Reflection;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ReflectionServiceTest extends UnitTestCase
{
    /**
     * @test
     *
     * Note:    Starting with PHP8.2 unserializing dynamic properties (undefined properties) emits a deprecation
     *          warning, which fails in normal tests. This moved here to avoid failing tests.
     */
    public function reflectionServiceIsResetDuringWakeUp(): void
    {
        $insecureString = file_get_contents(__DIR__ . '/Fixture/InsecureSerializedReflectionService.txt');
        $reflectionService = unserialize($insecureString);

        $reflectionClass = new \ReflectionClass($reflectionService);
        $classSchemaProperty = $reflectionClass->getProperty('classSchemata');
        $classSchemaProperty->setAccessible(true);

        self::assertSame([], $classSchemaProperty->getValue($reflectionService));
    }
}
