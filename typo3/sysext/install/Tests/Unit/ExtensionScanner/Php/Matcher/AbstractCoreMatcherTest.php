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

namespace TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher\Fixtures\AbstractCoreMatcherFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AbstractCoreMatcherTest extends UnitTestCase
{
    #[Test]
    #[DoesNotPerformAssertions]
    public function validateMatcherDefinitionsRunsFineWithProperDefinition(): void
    {
        $subject = new AbstractCoreMatcherFixture();
        $configuration = [
            'foo/bar->baz' => [
                'requiredArg1' => 42,
                'restFiles' => [
                    'aRest.rst',
                ],
            ],
        ];
        $subject->matcherDefinitions = $configuration;
        $subject->validateMatcherDefinitions(['requiredArg1']);
    }

    #[Test]
    public function validateMatcherDefinitionsThrowsIfRequiredArgIsNotInConfig(): void
    {
        $subject = new AbstractCoreMatcherFixture();
        $configuration = [
            'foo/bar->baz' => [
                'someNotRequiredConfig' => '',
                'restFiles' => [
                    'aRest.rst',
                ],
            ],
        ];
        $subject->matcherDefinitions = $configuration;
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1500492001);
        $subject->validateMatcherDefinitions(['requiredArg1']);
    }

    #[Test]
    public function validateMatcherDefinitionsThrowsWithMissingRestFiles(): void
    {
        $subject = new AbstractCoreMatcherFixture();
        $configuration = [
            'foo/bar->baz' => [
                'restFiles' => [],
            ],
        ];
        $subject->matcherDefinitions = $configuration;
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1500496068);
        $subject->validateMatcherDefinitions([]);
    }

    #[Test]
    public function validateMatcherDefinitionsThrowsWithEmptySingleRestFile(): void
    {
        $subject = new AbstractCoreMatcherFixture();
        $configuration = [
            'foo/bar->baz' => [
                'restFiles' => [
                    'foo.rst',
                    '',
                ],
            ],
        ];
        $subject->matcherDefinitions = $configuration;
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1500735983);
        $subject->validateMatcherDefinitions([]);
    }

    #[Test]
    public function initializeMethodNameArrayThrowsWithInvalidKeys(): void
    {
        $subject = new AbstractCoreMatcherFixture();
        $configuration = [
            'no\method\given' => [
                'restFiles' => [],
            ],
        ];
        $subject->matcherDefinitions = $configuration;
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1500557309);
        $subject->initializeFlatMatcherDefinitions();
    }
}
