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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AbsoluteUriPrefixRenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en'],
    ];

    protected array $configurationToUseInTestInstance = [
        'FE' => [
            'cacheHash' => [
                'excludedParameters' => ['useAbsoluteUrls', 'testCompressor'],
            ],
        ],
    ];

    /**
     * @var string[]
     */
    private array $definedResources = [
        'extensionCSS' => 'EXT:rte_ckeditor/Resources/Public/Css/contents.css',
        'externalCSS' => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css',
        'extensionJS' => 'EXT:core/Resources/Public/JavaScript/Contrib/jquery.js',
        'externalJS' => 'https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.11/handlebars.min.js',
        'localImage' => 'EXT:frontend/Resources/Public/Icons/Extension.svg',
    ];

    /**
     * @var string[]
     */
    private array $resolvedResources = [
        'externalCSS' => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css',
        'extensionJS' => 'typo3/sysext/core/Resources/Public/JavaScript/Contrib/jquery.js',
        'externalJS' => 'https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.11/handlebars.min.js',
        'localImage' => 'typo3/sysext/frontend/Resources/Public/Icons/Extension.svg',
        'link' => '/en/dummy-1-4-10',
    ];

    protected array $coreExtensionsToLoad = ['rte_ckeditor'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCsvDataSet(__DIR__ . '/../Fixtures/pages_frontend.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404]),
        );
        $this->setUpFrontendRootPage(
            1,
            ['EXT:frontend/Tests/Functional/Rendering/Fixtures/AbsoluteUriPrefixRenderingTest.typoscript']
        );
        $this->setTypoScriptConstantsToTemplateRecord(
            1,
            $this->compileTypoScriptConstants($this->definedResources)
        );
    }

    public static function urisAreRenderedUsingForceAbsoluteUrlsDataProvider(): \Generator
    {
        // no compression settings
        yield 'none - none' => [
            'none', 'none',
            [
                'local' => ['url' => '"/{{CANDIDATE}}"', 'count' => 1],
                'extension' => ['url' => '"/{{CANDIDATE}}\?\d+"', 'count' => 3],
                'external' => ['url' => '"{{CANDIDATE}}"', 'count' => 1],
                'link' => ['url' => 'href="{{CANDIDATE}}"', 'count' => 1],
            ],
        ];
        yield 'with-prefix - none' => [
            '1', 'none',
            [
                'local' => ['url' => '"http://localhost/{{CANDIDATE}}"', 'count' => 1],
                'extension' => ['url' => '"http://localhost/{{CANDIDATE}}\?\d+"', 'count' => 3],
                'external' => ['url' => '"{{CANDIDATE}}"', 'count' => 1],
                'link' => ['url' => 'href="http://localhost{{CANDIDATE}}"', 'count' => 1],
            ],
        ];
        // concatenation
        yield 'none - concatenate' => [
            '0', 'concatenate',
            [
                '!extension' => ['url' => '{{CANDIDATE}}', 'count' => 0],
                'local' => ['url' => '"/{{CANDIDATE}}"', 'count' => 1],
                'extension' => ['url' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"', 'count' => 2],
                'external' => ['url' => '"{{CANDIDATE}}"', 'count' => 1],
                'link' => ['url' => 'href="{{CANDIDATE}}"', 'count' => 1],
            ],
        ];
        yield 'with-prefix - concatenate' => [
            '1', 'concatenate',
            [
                '!extension' => ['url' => 'http://localhost/{{CANDIDATE}}', 'count' => 0],
                'local' => ['url' => '"http://localhost/{{CANDIDATE}}"', 'count' => 1],
                'extension' => ['url' => '"http://localhost/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"', 'count' => 2],
                'external' => ['url' => '"{{CANDIDATE}}"', 'count' => 1],
                'link' => ['url' => 'href="http://localhost{{CANDIDATE}}"', 'count' => 1],
            ],
        ];
        // compression
        yield 'none - compress' => [
            '0', 'compress',
            [
                '!extension' => ['url' => '/{{CANDIDATE}}', 'count' => 0],
                'local' => ['url' => '"/{{CANDIDATE}}"', 'count' => 1],
                'extension' => ['url' => '"/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"', 'count' => 2],
                'external' => ['url' => '"{{CANDIDATE}}"', 'count' => 1],
                'link' => ['url' => 'href="{{CANDIDATE}}"', 'count' => 1],
            ],
        ];
        yield 'with-prefix - compress' => [
            '1', 'compress',
            [
                '!extension' => ['url' => 'http://localhost/{{CANDIDATE}}', 'count' => 0],
                'local' => ['url' => '"http://localhost/{{CANDIDATE}}"', 'count' => 1],
                'extension' => ['url' => '"http://localhost/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"', 'count' => 2],
                'external' => ['url' => '"{{CANDIDATE}}"', 'count' => 1],
                'link' => ['url' => 'href="http://localhost{{CANDIDATE}}"', 'count' => 1],
            ],
        ];
        // concatenation & compression
        yield 'no prefix - concatenate-and-compress' => [
            '0', 'concatenate-and-compress',
            [
                '!extension' => ['url' => '/{{CANDIDATE}}', 'count' => 0],
                'local' => ['url' => '"/{{CANDIDATE}}"', 'count' => 1],
                'extension' => ['url' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"', 'count' => 2],
                'external' => ['url' => '"{{CANDIDATE}}"', 'count' => 1],
                'link' => ['url' => 'href="{{CANDIDATE}}"', 'count' => 1],
            ],
        ];
        yield 'with prefix - concatenate-and-compress' => [
            '1', 'concatenate-and-compress',
            [
                '!extension' => ['url' => 'http://localhost/{{CANDIDATE}}', 'count' => 0],
                'local' => ['url' => '"http://localhost/{{CANDIDATE}}"', 'count' => 1],
                'extension' => ['url' => '"http://localhost/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"', 'count' => 2],
                'external' => ['url' => '"{{CANDIDATE}}"', 'count' => 1],
                'link' => ['url' => 'href="http://localhost{{CANDIDATE}}"', 'count' => 1],
            ],
        ];
    }

    #[DataProvider('urisAreRenderedUsingForceAbsoluteUrlsDataProvider')]
    #[Test]
    public function urisAreRenderedUsingAbsRefPrefix(string $useAbsoluteUrls, string $compressorAspect, array $expectations): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withQueryParameters([
                'id' => 1,
                'useAbsoluteUrls' => $useAbsoluteUrls,
                'testCompressor' => $compressorAspect,
            ])
        );
        $content = (string)$response->getBody();

        foreach ($expectations as $type => $expectation) {
            $shallExist = true;
            if (str_starts_with($type, '!')) {
                $shallExist = false;
                $type = substr($type, 1);
            }
            $candidates = array_map(
                function (string $candidateKey) {
                    return $this->resolvedResources[$candidateKey];
                },
                array_filter(
                    array_keys($this->resolvedResources),
                    static function (string $candidateKey) use ($type) {
                        return str_starts_with($candidateKey, $type);
                    }
                )
            );
            foreach ($candidates as $candidate) {
                $pathInfo = pathinfo($candidate);
                $pattern = str_replace(
                    [
                        '{{CANDIDATE}}',
                        '{{CANDIDATE-FILENAME}}',
                        '{{CANDIDATE-EXTENSION}}',
                    ],
                    [
                        preg_quote($candidate, '#'),
                        preg_quote($pathInfo['filename'], '#'),
                        preg_quote($pathInfo['extension'] ?? '', '#'),
                    ],
                    $expectation['url']
                );

                if ($shallExist) {
                    self::assertMatchesRegularExpression(
                        '#' . $pattern . '#',
                        $content
                    );
                } else {
                    self::assertDoesNotMatchRegularExpression(
                        '#' . $pattern . '#',
                        $content
                    );
                }
                preg_match_all('#' . $pattern . '#', $content, $matches);
                self::assertCount($expectation['count'], $matches[0]);
            }
        }
    }

    /**
     * Adds TypoScript constants snippet to the existing template record
     */
    protected function setTypoScriptConstantsToTemplateRecord(int $pageId, string $constants, bool $append = false): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_template');

        $template = $connection->select(['uid', 'constants'], 'sys_template', ['pid' => $pageId, 'root' => 1])->fetchAssociative();
        if (empty($template)) {
            self::fail('Cannot find root template on page with id: "' . $pageId . '"');
        }
        $updateFields = [];
        $updateFields['constants'] = ($append ? $template['constants'] . LF : '') . $constants;
        $connection->update(
            'sys_template',
            $updateFields,
            ['uid' => $template['uid']]
        );
    }

    protected function compileTypoScriptConstants(array $constants): string
    {
        $lines = [];
        foreach ($constants as $constantName => $constantValue) {
            $lines[] = $constantName . ' = ' . $constantValue;
        }
        return implode(PHP_EOL, $lines);
    }
}
