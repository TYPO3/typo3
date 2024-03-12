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

namespace TYPO3\CMS\Core\Tests\Functional\LinkHandling;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\LinkHandling\LegacyLinkNotationConverter;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LegacyLinkNotationConverterTest extends FunctionalTestCase
{
    /**
     * @var array<string, non-empty-string>
     */
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/core/Tests/Functional/LinkHandling/Fixtures/fileadmin/' => 'fileadmin/',
    ];

    protected function tearDown(): void
    {
        GeneralUtility::rmdir(Environment::getPublicPath() . '/fileadmin', true);
        mkdir(Environment::getPublicPath() . '/fileadmin');
        GeneralUtility::rmdir(Environment::getPublicPath() . '/typo3temp/assets/_processed_', true);
        parent::tearDown();
    }

    public static function resolveReturnExpectedResultDataProvider(): \Generator
    {
        yield '#1 linkParameter looking like absolute path without tailing slash and not existing (file and folder) in FAL resolves to type URL' => [
            'linkParameter' => '/path/not/existing/in/fal',
            'expected' => [
                'type' => LinkService::TYPE_URL,
                'url' => '/path/not/existing/in/fal',
            ],
        ];
        yield '#2 linkParameter looking like absolute path with tailing slash and not existing (file and folder) in FAL resolves to type URL' => [
            'linkParameter' => '/path/not/existing/in/fal/',
            'expected' => [
                'type' => LinkService::TYPE_URL,
                'url' => '/path/not/existing/in/fal/',
            ],
        ];
        yield '#3 linkParameter looking like absolute path without tailing slash and existing folder in FAL resolves to type URL' => [
            'linkParameter' => '/fileadmin/some/path',
            'expected' => [
                'type' => LinkService::TYPE_URL,
                'url' => '/fileadmin/some/path',
            ],
        ];
        yield '#4 linkParameter looking like absolute path with tailing slash and existing folder in FAL resolves to type URL' => [
            'linkParameter' => '/fileadmin/some/path/',
            'expected' => [
                'type' => LinkService::TYPE_URL,
                'url' => '/fileadmin/some/path/',
            ],
        ];
        yield '#5 linkParameter for existing file returns type URL on fallback storage' => [
            'linkParameter' => '/fileadmin/some/path/typo3-logo.png',
            'expected' => [
                'type' => LinkService::TYPE_URL,
                'url' => '/fileadmin/some/path/typo3-logo.png',
            ],
        ];
    }

    #[DataProvider('resolveReturnExpectedResultDataProvider')]
    #[Test]
    public function resolveReturnsExpectedResult(
        string $linkParameter,
        array $expected,
    ): void {
        $subject = GeneralUtility::makeInstance(LegacyLinkNotationConverter::class);

        self::assertSame($expected, $subject->resolve($linkParameter));
    }
}
