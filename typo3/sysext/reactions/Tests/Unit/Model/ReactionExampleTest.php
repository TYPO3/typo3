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

namespace TYPO3\CMS\Reactions\Tests\Unit\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Reactions\Model\ReactionExample;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ReactionExampleTest extends UnitTestCase
{
    private const URL = 'https://example.org/typo3/reaction/5f9b25ce-d2bb-4ef5-9d80-83e5b1a2600b';

    public static function payloadIsComposedFromTheFieldMapDataProvider(): array
    {
        return [
            'placeholder becomes a key' => [
                ['header' => '${title}'],
                '{"title":"value"}',
            ],
            'dotted placeholder nests' => [
                ['header' => '${customer.name}'],
                '{"customer":{"name":"value"}}',
            ],
            'several placeholders in one value' => [
                ['header' => '${greeting} ${name}'],
                '{"greeting":"value","name":"value"}',
            ],
            'placeholders of several fields are merged' => [
                ['header' => '${title}', 'bodytext' => '${customer.name}'],
                '{"title":"value","customer":{"name":"value"}}',
            ],
            'the same placeholder twice is one key' => [
                ['header' => '${title}', 'subheader' => '${title}'],
                '{"title":"value"}',
            ],
            'a fixed value contributes nothing' => [
                ['header' => 'Always this', 'bodytext' => '${title}'],
                '{"title":"value"}',
            ],
            'a map of fixed values only falls back to a minimal body' => [
                ['header' => 'Always this', 'CType' => 'text'],
                '{"key":"value"}',
            ],
            'an empty map falls back to a minimal body' => [
                [],
                '{"key":"value"}',
            ],
            'non string values are ignored' => [
                ['hidden' => 1, 'starttime' => null, 'header' => '${title}'],
                '{"title":"value"}',
            ],
            'an empty placeholder is ignored' => [
                ['header' => '${}', 'bodytext' => '${title}'],
                '{"title":"value"}',
            ],
            'a placeholder with an empty segment is ignored' => [
                ['header' => '${a..b}', 'bodytext' => '${title}'],
                '{"title":"value"}',
            ],
            'a scalar path later used as a parent becomes the parent' => [
                ['header' => '${a}', 'bodytext' => '${a.b}'],
                '{"a":{"b":"value"}}',
            ],
            'a parent path later used as a scalar stays the parent' => [
                ['header' => '${a.b}', 'bodytext' => '${a}'],
                '{"a":{"b":"value"}}',
            ],
            'a slash in a path is not escaped away' => [
                ['header' => '${a/b}'],
                '{"a/b":"value"}',
            ],
        ];
    }

    #[DataProvider('payloadIsComposedFromTheFieldMapDataProvider')]
    #[Test]
    public function payloadIsComposedFromTheFieldMap(array $fields, string $expected): void
    {
        $command = ReactionExample::forFieldMap(self::URL, $fields)->getCurlCommand();
        self::assertStringContainsString(" -d '" . $expected . "'", $command);
    }

    #[Test]
    public function curlCommandDeclaresTheHeadersTheEndpointActuallyReads(): void
    {
        $command = ReactionExample::forFieldMap(self::URL, ['header' => '${title}'])->getCurlCommand();

        self::assertSame(
            "curl -X POST 'https://example.org/typo3/reaction/5f9b25ce-d2bb-4ef5-9d80-83e5b1a2600b' \\\n"
            . "  -H 'Content-Type: application/json' \\\n"
            . "  -H 'x-api-key: <your-secret>' \\\n"
            . "  -d '{\"title\":\"value\"}'",
            $command
        );
    }

    #[Test]
    public function acceptHeaderIsNotSuggested(): void
    {
        $command = ReactionExample::forFieldMap(self::URL, ['header' => '${title}'])->getCurlCommand();
        self::assertStringNotContainsString('accept:', $command);
    }

    #[Test]
    public function singleQuoteInAPlaceholderCannotBreakOutOfTheQuoting(): void
    {
        $command = ReactionExample::forFieldMap(self::URL, ['header' => "\${it's}"])->getCurlCommand();
        self::assertStringContainsString("-d '{\"it'\\''s\":\"value\"}'", $command);
    }

    #[Test]
    public function urlIsExposedForItsOwnDisplay(): void
    {
        self::assertSame(self::URL, ReactionExample::forFieldMap(self::URL, [])->getUrl());
    }
}
