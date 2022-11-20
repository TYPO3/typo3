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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class AbsoluteUriPrefixRenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var string[]
     */
    private array $definedResources = [
        'absoluteCSS' => '/typo3/sysext/backend/Resources/Public/Css/backend.css',
        'relativeCSS' => 'typo3/sysext/backend/Resources/Public/Css/backend.css',
        'extensionCSS' => 'EXT:rte_ckeditor/Resources/Public/Css/contents.css',
        'externalCSS' => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css',
        'absoluteJS' => '/typo3/sysext/backend/Resources/Public/JavaScript/backend.js',
        'relativeJS' => 'typo3/sysext/core/Resources/Public/JavaScript/Contrib/autosize.js',
        'extensionJS' => 'EXT:core/Resources/Public/JavaScript/Contrib/jquery.js',
        'externalJS' => 'https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.11/handlebars.min.js',
        'localImage' => 'typo3/sysext/frontend/Resources/Public/Icons/Extension.svg',
    ];

    /**
     * @var string[]
     */
    private array $resolvedResources = [
        'relativeCSS' => 'typo3/sysext/backend/Resources/Public/Css/backend.css',
        'extensionCSS' => 'typo3/sysext/rte_ckeditor/Resources/Public/Css/contents.css',
        'externalCSS' => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css',
        'relativeJS' => 'typo3/sysext/core/Resources/Public/JavaScript/Contrib/autosize.js',
        'extensionJS' => 'typo3/sysext/core/Resources/Public/JavaScript/Contrib/jquery.js',
        'externalJS' => 'https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.11/handlebars.min.js',
        'localImage' => 'typo3/sysext/frontend/Resources/Public/Icons/Extension.svg',
        'link' => '/en/dummy-1-4-10',
    ];

    protected array $coreExtensionsToLoad = ['rte_ckeditor'];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCsvDataSet(__DIR__ . '/../../../../core/Tests/Functional/Fixtures/pages.csv');
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

    public function urisAreRenderedUsingForceAbsoluteUrlsDataProvider(): \Generator
    {
        // no compression settings
        yield 'none - none' => [
            'none', 'none',
            [
                'absolute' => '"/{{CANDIDATE}}"',
                'local' => '"/{{CANDIDATE}}"',
                'relative' => '"/{{CANDIDATE}}\?\d+"',
                'extension' => '"/{{CANDIDATE}}\?\d+"',
                'external' => '"{{CANDIDATE}}"',
                'link' => 'href="{{CANDIDATE}}"',
            ],
        ];
        yield 'with-prefix - none' => [
            '1', 'none',
            [
                'absolute' => '"http://localhost/{{CANDIDATE}}"',
                'local' => '"http://localhost/{{CANDIDATE}}"',
                'relative' => '"http://localhost/{{CANDIDATE}}\?\d+"',
                'extension' => '"http://localhost/{{CANDIDATE}}\?\d+"',
                'external' => '"{{CANDIDATE}}"',
                'link' => 'href="http://localhost{{CANDIDATE}}"',
            ],
        ];
        // concatenation
        yield 'none - concatenate' => [
            '0', 'concatenate',
            [
                '!absolute' => '{{CANDIDATE}}',
                '!relative' => '{{CANDIDATE}}',
                '!extension' => '{{CANDIDATE}}',
                'absolute' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'local' => '"/{{CANDIDATE}}"',
                'relative' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'extension' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'external' => '"{{CANDIDATE}}"',
                'link' => 'href="{{CANDIDATE}}"',
            ],
        ];
        yield 'with-prefix - concatenate' => [
            '1', 'concatenate',
            [
                '!absolute' => 'http://localhost/{{CANDIDATE}}',
                '!relative' => 'http://localhost/{{CANDIDATE}}',
                '!extension' => 'http://localhost/{{CANDIDATE}}',
                'absolute' => '"http://localhost/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'local' => '"http://localhost/{{CANDIDATE}}"',
                'relative' => '"http://localhost/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'extension' => '"http://localhost/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'external' => '"{{CANDIDATE}}"',
                'link' => 'href="http://localhost{{CANDIDATE}}"',
            ],
        ];
        // compression
        yield 'none - compress' => [
            '0', 'compress',
            [
                '!absolute' => '{{CANDIDATE}}',
                '!relative' => '/{{CANDIDATE}}',
                '!extension' => '/{{CANDIDATE}}',
                'absolute' => '"/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'local' => '"/{{CANDIDATE}}"',
                'relative' => '"/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'extension' => '"/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'external' => '"{{CANDIDATE}}"',
                'link' => 'href="{{CANDIDATE}}"',
            ],
        ];
        yield 'with-prefix - compress' => [
            '1', 'compress',
            [
                '!absolute' => 'http://localhost/{{CANDIDATE}}',
                '!relative' => 'http://localhost/{{CANDIDATE}}',
                '!extension' => 'http://localhost/{{CANDIDATE}}',
                'absolute' => '"http://localhost/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'local' => '"http://localhost/{{CANDIDATE}}"',
                'relative' => '"http://localhost/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'extension' => '"http://localhost/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'external' => '"{{CANDIDATE}}"',
                'link' => 'href="http://localhost{{CANDIDATE}}"',
            ],
        ];
        // concatenation & compression
        yield 'no prefix - concatenate-and-compress' => [
            '0', 'concatenate-and-compress',
            [
                '!absolute' => '{{CANDIDATE}}',
                '!relative' => '/{{CANDIDATE}}',
                '!extension' => '/{{CANDIDATE}}',
                'absolute' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'local' => '"/{{CANDIDATE}}"',
                'relative' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'extension' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'external' => '"{{CANDIDATE}}"',
                'link' => 'href="{{CANDIDATE}}"',
            ],
        ];
        yield 'with prefix - concatenate-and-compress' => [
            '1', 'concatenate-and-compress',
            [
                '!absolute' => 'http://localhost/{{CANDIDATE}}',
                '!relative' => 'http://localhost/{{CANDIDATE}}',
                '!extension' => 'http://localhost/{{CANDIDATE}}',
                'absolute' => '"http://localhost/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'local' => '"http://localhost/{{CANDIDATE}}"',
                'relative' => '"http://localhost/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'extension' => '"http://localhost/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                'external' => '"{{CANDIDATE}}"',
                'link' => 'href="http://localhost{{CANDIDATE}}"',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider urisAreRenderedUsingForceAbsoluteUrlsDataProvider
     */
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
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_template');

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

    /**
     * @return string
     */
    protected function compileTypoScriptConstants(array $constants): string
    {
        $lines = [];
        foreach ($constants as $constantName => $constantValue) {
            $lines[] = $constantName . ' = ' . $constantValue;
        }
        return implode(PHP_EOL, $lines);
    }
}
