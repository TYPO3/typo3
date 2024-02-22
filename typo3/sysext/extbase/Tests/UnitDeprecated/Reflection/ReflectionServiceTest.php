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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ReflectionServiceTest extends UnitTestCase
{
    #[Test]
    public function reflectionServiceIsResetDuringWakeUp(): void
    {
        $insecureString = file_get_contents(__DIR__ . '/Fixture/InsecureSerializedReflectionService.txt');
        // Note: We need to use the silence operator here for `unserialize()`, otherwise PHP8.3 would emit a warning
        //       because of unneeded bytes in the content which needs to be unserialized.
        $reflectionService = @unserialize($insecureString);

        $reflectionClass = new \ReflectionClass($reflectionService);
        $classSchemaProperty = $reflectionClass->getProperty('classSchemata');

        self::assertSame([], $classSchemaProperty->getValue($reflectionService));
    }
}
