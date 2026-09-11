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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Remote\Dto;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extensionmanager\Remote\Dto\TerExtensionListResult;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TerExtensionListResultTest extends UnitTestCase
{
    #[Test]
    public function fromArrayParsesTheTerRestListEnvelope(): void
    {
        $result = TerExtensionListResult::fromArray([
            'results' => 42,
            'page' => 2,
            'per_page' => 20,
            'filter' => ['typo3_version' => 13],
            'extensions' => [
                [
                    'key' => 'news',
                    'owner' => 'georg',
                    'downloads' => 100,
                    'version_count' => 5,
                    'meta' => ['composer_name' => 'georgringer/news'],
                    'current_version' => ['number' => '11.0.0', 'title' => 'News'],
                ],
                // Malformed entries are skipped, not fatal.
                ['no_key' => true],
            ],
        ]);

        self::assertSame(42, $result->totalResults);
        self::assertCount(1, $result->extensions);
        self::assertSame('news', $result->extensions[0]->key);
        self::assertSame('georgringer/news', $result->extensions[0]->composerName);
        self::assertSame('11.0.0', $result->extensions[0]->currentVersion->number);
    }

    #[Test]
    public function fromArrayFallsBackToCountsWhenMetaMissing(): void
    {
        $result = TerExtensionListResult::fromArray([
            'extensions' => [
                ['key' => 'a', 'current_version' => ['number' => '1.0.0']],
                ['key' => 'b', 'current_version' => ['number' => '2.0.0']],
            ],
        ]);

        self::assertSame(2, $result->totalResults);
    }
}
