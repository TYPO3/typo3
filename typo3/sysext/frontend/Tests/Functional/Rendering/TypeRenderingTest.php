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

namespace TYPO3\CMS\Frontend\Tests\Functional\Rendering;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TypeRenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'websiteTitle' => 'Site EN'],
    ];

    public static function typesDataProvider(): \Iterator
    {
        yield [
            'typoScriptFile' => 'EXT:frontend/Tests/Functional/Rendering/Fixtures/TypeRenderingTestNoExplicitTypeNum.typoscript',
            'type' => 0,
            'expected' => 'noExplicitTypeNum',
        ];
        yield [
            'typoScriptFile' => 'EXT:frontend/Tests/Functional/Rendering/Fixtures/TypeRenderingTestNoExplicitTypeNum.typoscript',
            'type' => null,
            'expected' => 'noExplicitTypeNum',
        ];
        yield [
            'typoScriptFile' => 'EXT:frontend/Tests/Functional/Rendering/Fixtures/TypeRenderingTestTypeNumZero.typoscript',
            'type' => 0,
            'expected' => 'typeNumZero',
        ];
        yield [
            'typoScriptFile' => 'EXT:frontend/Tests/Functional/Rendering/Fixtures/TypeRenderingTestTypeNumZero.typoscript',
            'type' => null,
            'expected' => 'typeNumZero',
        ];
        yield [
            'typoScriptFile' => 'EXT:frontend/Tests/Functional/Rendering/Fixtures/TypeRenderingTestTwoPageNoNum.typoscript',
            'type' => 0,
            'expected' => 'firstPageContent',
        ];
        yield [
            'typoScriptFile' => 'EXT:frontend/Tests/Functional/Rendering/Fixtures/TypeRenderingTestTwoPageNoNum.typoscript',
            'type' => null,
            'expected' => 'firstPageContent',
        ];
        yield [
            'typoScriptFile' => 'EXT:frontend/Tests/Functional/Rendering/Fixtures/TypeRenderingTestVariousTypeNums.typoscript',
            'type' => 0,
            'expected' => 'defaultPage',
        ];
        yield [
            'typoScriptFile' => 'EXT:frontend/Tests/Functional/Rendering/Fixtures/TypeRenderingTestVariousTypeNums.typoscript',
            'type' => 42,
            'expected' => 'intTypeContent',
        ];
        yield [
            'typoScriptFile' => 'EXT:frontend/Tests/Functional/Rendering/Fixtures/TypeRenderingTestVariousTypeNums.typoscript',
            'type' => 'foo',
            'expected' => 'stringTypeContent',
        ];
    }

    #[DataProvider('typesDataProvider')]
    #[Test]
    public function typesAreRendered(string $typoScriptFile, int|string|null $type, string $expected): void
    {
        $this->importCsvDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->setUpFrontendRootPage(1, [$typoScriptFile]);
        $this->writeSiteConfiguration(
            'testing',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        $queryParameters = [
            'id' => 1,
        ];
        if ($type !== null) {
            $queryParameters['type'] = $type;
        }
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withQueryParameters($queryParameters));
        self::assertStringContainsString($expected, (string)$response->getBody());
    }
}
