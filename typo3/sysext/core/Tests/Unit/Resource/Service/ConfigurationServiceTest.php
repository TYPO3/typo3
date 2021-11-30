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

use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Service\ConfigurationService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ConfigurationServiceTest extends UnitTestCase
{
    /**
     * @test
     */
    public function serializeSubstitutesFileObject(): void
    {
        $fileMock = $this->createMock(ProcessedFile::class);
        $fileMock->method('toArray')->willReturn(['id' => '1:test.jpg']);
        $configuration = [
            'width' => '2000c',
            'height' => '300c-60',
            'foo' => $fileMock,
            'maskImages' => [
                'maskImage' => $fileMock,
                'backgroundImage' => $fileMock,
                'bar' => 'bar1',
            ],
        ];
        $expected = [
            'width' => '2000c',
            'height' => '300c-60',
            'foo' => $fileMock->toArray(),
            'maskImages' => [
                'maskImage' => $fileMock->toArray(),
                'backgroundImage' => $fileMock->toArray(),
                'bar' => 'bar1',
            ],
        ];
        self::assertSame(serialize($expected), (new ConfigurationService())->serialize($configuration));
    }
}
