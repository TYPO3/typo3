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
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LinkServiceTest extends FunctionalTestCase
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

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function resolveReturnExpectedResultDataProvider(): array
    {
        // Reuse DataProviders of concrete link-type testcases
        return [
            ...LegacyLinkNotationConverterTest::resolveReturnExpectedResultDataProvider(),
            // @todo Add tests for other link types and combine them here for the link service level
        ];
    }

    #[DataProvider('resolveReturnExpectedResultDataProvider')]
    #[Test]
    public function resolveReturnsExpectedResult(
        string $linkParameter,
        array $expected,
    ): void {
        $subject = $this->get(LinkService::class);

        self::assertSame($expected, $subject->resolve($linkParameter));
    }
}
