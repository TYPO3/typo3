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

final class UriPrefixRenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en'],
    ];

    protected array $configurationToUseInTestInstance = [
        'FE' => [
            'cacheHash' => [
                'excludedParameters' => ['testAbsRefPrefix', 'testCompressor'],
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
        'extensionCSS' => 'typo3/sysext/rte_ckeditor/Resources/Public/Css/contents.css',
        'externalCSS' => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css',
        'extensionJS' => 'typo3/sysext/core/Resources/Public/JavaScript/Contrib/jquery.js',
        'externalJS' => 'https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.11/handlebars.min.js',
        'localImage' => 'typo3/sysext/frontend/Resources/Public/Icons/Extension.svg',
    ];

    protected array $coreExtensionsToLoad = ['rte_ckeditor'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCsvDataSet(__DIR__ . '/../Fixtures/pages_frontend.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404]),
        );
        $this->setUpFrontendRootPage(
            1,
            ['EXT:frontend/Tests/Functional/Rendering/Fixtures/UriPrefixRenderingTest.typoscript']
        );
        $this->setTypoScriptConstantsToTemplateRecord(
            1,
            $this->compileTypoScriptConstants($this->definedResources)
        );
    }

    public static function urisAreRenderedUsingAbsRefPrefixDataProvider(): array
    {
        return [
            // no compression settings
            'none - none' => [
                'none', 'none',
                [
                    'local' => '"{{CANDIDATE}}"',
                    'extension' => '"/{{CANDIDATE}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'auto - none' => [
                'auto', 'none',
                [
                    'local' => '"/{{CANDIDATE}}"',
                    'extension' => '"/{{CANDIDATE}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'absolute-with-host - none' => [
                'absolute-with-host', 'none',
                [
                    'local' => '"http://localhost/{{CANDIDATE}}"',
                    'extension' => '"http://localhost/{{CANDIDATE}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'absolute-without-host - none' => [
                'absolute-without-host', 'none',
                [
                    'local' => '"/{{CANDIDATE}}"',
                    'extension' => '"/{{CANDIDATE}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            // concatenation
            'none - concatenate' => [
                'none', 'concatenate',
                [
                    '!extension' => '{{CANDIDATE}}',
                    'local' => '"{{CANDIDATE}}"',
                    'extension' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'auto - concatenate' => [
                'auto', 'concatenate',
                [
                    '!extension' => '{{CANDIDATE}}',
                    'local' => '"/{{CANDIDATE}}"',
                    'extension' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'absolute-with-host - concatenate' => [
                'absolute-with-host', 'concatenate',
                [
                    '!extension' => '{{CANDIDATE}}',
                    'local' => '"http://localhost/{{CANDIDATE}}"',
                    'extension' => '"http://localhost/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'absolute-without-host - concatenate' => [
                'absolute-without-host', 'concatenate',
                [
                    '!extension' => '{{CANDIDATE}}',
                    'local' => '"/{{CANDIDATE}}"',
                    'extension' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            // compression
            'none - compress' => [
                'none', 'compress',
                [
                    '!extension' => '{{CANDIDATE}}',
                    'local' => '"{{CANDIDATE}}"',
                    'extension' => '"/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'auto - compress' => [
                'auto', 'compress',
                [
                    '!extension' => '{{CANDIDATE}}',
                    'local' => '"/{{CANDIDATE}}"',
                    'extension' => '"/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'absolute-with-host - compress' => [
                'absolute-with-host', 'compress',
                [
                    '!extension' => '{{CANDIDATE}}',
                    'local' => '"http://localhost/{{CANDIDATE}}"',
                    'extension' => '"http://localhost/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'absolute-without-host - compress' => [
                'absolute-without-host', 'compress',
                [
                    '!extension' => '{{CANDIDATE}}',
                    'local' => '"/{{CANDIDATE}}"',
                    'extension' => '"/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            // concatenation & compression
            'none - concatenate-and-compress' => [
                'none', 'concatenate-and-compress',
                [
                    '!extension' => '{{CANDIDATE}}',
                    'local' => '"{{CANDIDATE}}"',
                    'extension' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'auto - concatenate-and-compress' => [
                'auto', 'concatenate-and-compress',
                [
                    '!extension' => '{{CANDIDATE}}',
                    'local' => '"/{{CANDIDATE}}"',
                    'extension' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'absolute-with-host - concatenate-and-compress' => [
                'absolute-with-host', 'concatenate-and-compress',
                [
                    '!extension' => '{{CANDIDATE}}',
                    'local' => '"http://localhost/{{CANDIDATE}}"',
                    'extension' => '"http://localhost/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'absolute-without-host - concatenate-and-compress' => [
                'absolute-without-host', 'concatenate-and-compress',
                [
                    '!extension' => '{{CANDIDATE}}',
                    'local' => '"/{{CANDIDATE}}"',
                    'extension' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
        ];
    }

    #[DataProvider('urisAreRenderedUsingAbsRefPrefixDataProvider')]
    #[Test]
    public function urisAreRenderedUsingAbsRefPrefix(string $absRefPrefixAspect, string $compressorAspect, array $expectations): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withQueryParameters([
                'id' => 1,
                'testAbsRefPrefix' => $absRefPrefixAspect,
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
                    $expectation
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
