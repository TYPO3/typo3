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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Service\ConfigurationService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ConfigurationServiceTest extends UnitTestCase
{
    #[Test]
    public function serializeSubstitutesFileObject(): void
    {
        $fileStub = self::createStub(ProcessedFile::class);
        $fileStub->method('toArray')->willReturn(['id' => '1:test.jpg']);
        $configuration = [
            'width' => '2000c',
            'height' => '300c-60',
            'foo' => $fileStub,
            'maskImages' => [
                'maskImage' => $fileStub,
                'backgroundImage' => $fileStub,
                'bar' => 'bar1',
            ],
        ];
        $expected = [
            'width' => '2000c',
            'height' => '300c-60',
            'foo' => $fileStub->toArray(),
            'maskImages' => [
                'maskImage' => $fileStub->toArray(),
                'backgroundImage' => $fileStub->toArray(),
                'bar' => 'bar1',
            ],
        ];
        self::assertSame(serialize($expected), (new ConfigurationService())->serialize($configuration));
    }
}
