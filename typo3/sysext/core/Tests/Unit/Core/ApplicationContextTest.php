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

namespace TYPO3\CMS\Core\Tests\Unit\Core;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Exception;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the ApplicationContext class
 */
final class ApplicationContextTest extends UnitTestCase
{
    /**
     * Data provider with allowed contexts.
     */
    public static function allowedContexts(): array
    {
        return [
            ['Production'],
            ['Testing'],
            ['Development'],

            ['Development/MyLocalComputer'],
            ['Development/MyLocalComputer/Foo'],
            ['Production/SpecialDeployment/LiveSystem'],
        ];
    }

    #[DataProvider('allowedContexts')]
    #[Test]
    public function contextStringCanBeSetInConstructorAndReadByCallingToString($allowedContext): void
    {
        $context = new ApplicationContext($allowedContext);
        self::assertSame($allowedContext, (string)$context);
    }

    /**
     * Data provider with forbidden contexts.
     */
    public static function forbiddenContexts(): array
    {
        return [
            ['MySpecialContext'],
            ['Testing123'],
            ['DevelopmentStuff'],
            ['DevelopmentStuff/FooBar'],
        ];
    }

    #[DataProvider('forbiddenContexts')]
    #[Test]
    public function constructorThrowsExceptionIfMainContextIsForbidden($forbiddenContext): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1335436551);

        new ApplicationContext($forbiddenContext);
    }

    /**
     * Data provider with expected is*() values for various contexts.
     */
    public static function isMethods(): array
    {
        return [
            'Development' => [
                'contextName' => 'Development',
                'isDevelopment' => true,
                'isProduction' => false,
                'isTesting' => false,
                'parentContext' => null,
            ],
            'Development/YourSpecialContext' => [
                'contextName' => 'Development/YourSpecialContext',
                'isDevelopment' => true,
                'isProduction' => false,
                'isTesting' => false,
                'parentContext' => 'Development',
            ],

            'Production' => [
                'contextName' => 'Production',
                'isDevelopment' => false,
                'isProduction' => true,
                'isTesting' => false,
                'parentContext' => null,
            ],
            'Production/MySpecialContext' => [
                'contextName' => 'Production/MySpecialContext',
                'isDevelopment' => false,
                'isProduction' => true,
                'isTesting' => false,
                'parentContext' => 'Production',
            ],

            'Testing' => [
                'contextName' => 'Testing',
                'isDevelopment' => false,
                'isProduction' => false,
                'isTesting' => true,
                'parentContext' => null,
            ],
            'Testing/MySpecialContext' => [
                'contextName' => 'Testing/MySpecialContext',
                'isDevelopment' => false,
                'isProduction' => false,
                'isTesting' => true,
                'parentContext' => 'Testing',
            ],
        ];
    }

    #[DataProvider('isMethods')]
    #[Test]
    public function contextMethodsReturnTheCorrectValues($contextName, $isDevelopment, $isProduction, $isTesting, $parentContext): void
    {
        $context = new ApplicationContext($contextName);
        self::assertSame($isDevelopment, $context->isDevelopment());
        self::assertSame($isProduction, $context->isProduction());
        self::assertSame($isTesting, $context->isTesting());
        self::assertSame((string)$parentContext, (string)$context->getParent());
    }

    #[Test]
    public function parentContextIsConnectedRecursively(): void
    {
        $context = new ApplicationContext('Production/Foo/Bar');
        $parentContext = $context->getParent();
        self::assertSame('Production/Foo', (string)$parentContext);

        $rootContext = $parentContext->getParent();
        self::assertSame('Production', (string)$rootContext);
    }
}
