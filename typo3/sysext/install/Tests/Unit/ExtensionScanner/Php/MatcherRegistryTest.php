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

namespace TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Install\ExtensionScanner\CodeScannerInterface;
use TYPO3\CMS\Install\ExtensionScanner\Php\MatcherRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MatcherRegistryTest extends UnitTestCase
{
    #[Test]
    public function everyConfigurationIsCompleteAndResolvable(): void
    {
        $configurations = new MatcherRegistry()->getMatcherConfigurations();

        self::assertNotSame([], $configurations);
        foreach ($configurations as $configuration) {
            self::assertArrayHasKey('class', $configuration);
            self::assertArrayHasKey('configurationFile', $configuration);
            self::assertTrue(
                is_subclass_of($configuration['class'], CodeScannerInterface::class),
                $configuration['class'] . ' does not implement CodeScannerInterface'
            );
            self::assertStringStartsWith(
                'EXT:install/Configuration/ExtensionScanner/Php/',
                $configuration['configurationFile']
            );
        }
    }

    #[Test]
    public function configurationsAreFreeOfDuplicateClasses(): void
    {
        $classes = array_column(new MatcherRegistry()->getMatcherConfigurations(), 'class');

        self::assertSame(array_unique($classes), $classes);
    }
}
