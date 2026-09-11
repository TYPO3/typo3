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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Remote;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extensionmanager\Remote\Dto\TerExtension;
use TYPO3\CMS\Extensionmanager\Remote\TerExtensionMapper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TerExtensionMapperTest extends UnitTestCase
{
    public static function dependencyConstraintsDataProvider(): \Generator
    {
        yield 'range as the REST API sends it' => ['>=13.4.29 <=14.3.99', '13.4.29', '14.3.99'];
        yield 'lower bound only' => ['>=8.2.0', '8.2.0', ''];
        yield 'upper bound only' => ['<=14.3.99', '', '14.3.99'];
        yield 'range in ext_emconf notation' => ['13.4.0-14.4.99', '13.4.0', '14.4.99'];
        yield 'no constraint' => ['', '', ''];
    }

    #[Test]
    #[DataProvider('dependencyConstraintsDataProvider')]
    public function dependencyConstraintsAreTurnedIntoVersionBounds(string $constraint, string $lowest, string $highest): void
    {
        $remoteExtension = TerExtension::fromArray([
            'key' => 'news',
            'current_version' => [
                'number' => '14.1.1',
                'dependencies' => ['typo3' => $constraint],
            ],
        ]);

        $mapper = new TerExtensionMapper();
        $extension = $mapper->map($remoteExtension);

        $dependency = $extension->getTypo3Dependency();
        self::assertSame($lowest, $dependency->getLowestVersion());
        self::assertSame($highest, $dependency->getHighestVersion());
    }
}
