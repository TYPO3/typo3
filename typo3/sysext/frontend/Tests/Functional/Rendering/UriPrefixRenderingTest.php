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

class UriPrefixRenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var string[]
     */
    private $definedResources = [
        'absoluteCSS' => '/typo3/sysext/backend/Resources/Public/Css/backend.css',
        'relativeCSS' => 'typo3/sysext/core/Resources/Public/Css/errorpage.css',
        'extensionCSS' => 'EXT:rte_ckeditor/Resources/Public/Css/contents.css',
        'externalCSS' => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css',
        'absoluteJS' => '/typo3/sysext/backend/Resources/Public/JavaScript/backend.js',
        'relativeJS' => 'typo3/sysext/core/Resources/Public/JavaScript/Contrib/autosize.js',
        'extensionJS' => 'EXT:core/Resources/Public/JavaScript/Contrib/jquery.autocomplete.js',
        'externalJS' => 'https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.11/handlebars.min.js',
        'localImage' => 'typo3/sysext/frontend/Resources/Public/Icons/Extension.png',
    ];

    /**
     * @var string[]
     */
    private $resolvedResources = [
        'relativeCSS' => 'typo3/sysext/core/Resources/Public/Css/errorpage.css',
        'extensionCSS' => 'typo3/sysext/rte_ckeditor/Resources/Public/Css/contents.css',
        'externalCSS' => 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css',
        'relativeJS' => 'typo3/sysext/core/Resources/Public/JavaScript/Contrib/autosize.js',
        'extensionJS' => 'typo3/sysext/core/Resources/Public/JavaScript/Contrib/jquery.autocomplete.js',
        'externalJS' => 'https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.11/handlebars.min.js',
        'localImage' => 'typo3/sysext/frontend/Resources/Public/Icons/Extension.png',
    ];

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'core', 'frontend', 'backend', 'rte_ckeditor',
    ];

    /**
     * @var string[]
     */
    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/AdditionalConfiguration.php' => 'typo3conf/AdditionalConfiguration.php',
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet('EXT:core/Tests/Functional/Fixtures/pages.xml');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ],
            [
                $this->buildErrorHandlingConfiguration('Fluid', [404])
            ]
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

    public function urisAreRenderedUsingAbsRefPrefixDataProvider(): array
    {
        return [
            // no compression settings
            'none - none' => [
                'none', 'none',
                [
                    'absolute' => '"{{CANDIDATE}}"',
                    'local' => '"{{CANDIDATE}}"',
                    'relative' => '"{{CANDIDATE}}\?\d+"',
                    'extension' => '"{{CANDIDATE}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'auto - none' => [
                'auto', 'none',
                [
                    'absolute' => '"{{CANDIDATE}}"',
                    'local' => '"/{{CANDIDATE}}"',
                    'relative' => '"/{{CANDIDATE}}\?\d+"',
                    'extension' => '"/{{CANDIDATE}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'absolute-with-host - none' => [
                'absolute-with-host', 'none',
                [
                    'absolute' => '"{{CANDIDATE}}"',
                    'local' => '"http://localhost/{{CANDIDATE}}"',
                    'relative' => '"http://localhost/{{CANDIDATE}}\?\d+"',
                    'extension' => '"http://localhost/{{CANDIDATE}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'absolute-without-host - none' => [
                'absolute-without-host', 'none',
                [
                    'absolute' => '"{{CANDIDATE}}"',
                    'local' => '"/{{CANDIDATE}}"',
                    'relative' => '"/{{CANDIDATE}}\?\d+"',
                    'extension' => '"/{{CANDIDATE}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            // concatenation
            'none - concatenate' => [
                'none', 'concatenate',
                [
                    '!absolute' => '{{CANDIDATE}}',
                    '!relative' => '{{CANDIDATE}}',
                    '!extension' => '{{CANDIDATE}}',
                    'absolute' => '"typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'local' => '"{{CANDIDATE}}"',
                    'relative' => '"typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'extension' => '"typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'auto - concatenate' => [
                'auto', 'concatenate',
                [
                    '!absolute' => '{{CANDIDATE}}',
                    '!relative' => '{{CANDIDATE}}',
                    '!extension' => '{{CANDIDATE}}',
                    'absolute' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'local' => '"/{{CANDIDATE}}"',
                    'relative' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'extension' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'absolute-with-host - concatenate' => [
                'absolute-with-host', 'concatenate',
                [
                    '!absolute' => '{{CANDIDATE}}',
                    '!relative' => '{{CANDIDATE}}',
                    '!extension' => '{{CANDIDATE}}',
                    'absolute' => '"http://localhost/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'local' => '"http://localhost/{{CANDIDATE}}"',
                    'relative' => '"http://localhost/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'extension' => '"http://localhost/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'absolute-without-host - concatenate' => [
                'absolute-without-host', 'concatenate',
                [
                    '!absolute' => '{{CANDIDATE}}',
                    '!relative' => '{{CANDIDATE}}',
                    '!extension' => '{{CANDIDATE}}',
                    'absolute' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'local' => '"/{{CANDIDATE}}"',
                    'relative' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'extension' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            // compression
            'none - compress' => [
                'none', 'compress',
                [
                    '!absolute' => '{{CANDIDATE}}',
                    '!relative' => '{{CANDIDATE}}',
                    '!extension' => '{{CANDIDATE}}',
                    'absolute' => '"typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'local' => '"{{CANDIDATE}}"',
                    'relative' => '"typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'extension' => '"typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'auto - compress' => [
                'auto', 'compress',
                [
                    '!absolute' => '{{CANDIDATE}}',
                    '!relative' => '{{CANDIDATE}}',
                    '!extension' => '{{CANDIDATE}}',
                    'absolute' => '"/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'local' => '"/{{CANDIDATE}}"',
                    'relative' => '"/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'extension' => '"/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'absolute-with-host - compress' => [
                'absolute-with-host', 'compress',
                [
                    '!absolute' => '{{CANDIDATE}}',
                    '!relative' => '{{CANDIDATE}}',
                    '!extension' => '{{CANDIDATE}}',
                    'absolute' => '"http://localhost/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'local' => '"http://localhost/{{CANDIDATE}}"',
                    'relative' => '"http://localhost/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'extension' => '"http://localhost/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'absolute-without-host - compress' => [
                'absolute-without-host', 'compress',
                [
                    '!absolute' => '{{CANDIDATE}}',
                    '!relative' => '{{CANDIDATE}}',
                    '!extension' => '{{CANDIDATE}}',
                    'absolute' => '"/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'local' => '"/{{CANDIDATE}}"',
                    'relative' => '"/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'extension' => '"/typo3temp/assets/compressed/{{CANDIDATE-FILENAME}}-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            // concatenation & compression
            'none - concatenate-and-compress' => [
                'none', 'concatenate-and-compress',
                [
                    '!absolute' => '{{CANDIDATE}}',
                    '!relative' => '{{CANDIDATE}}',
                    '!extension' => '{{CANDIDATE}}',
                    'absolute' => '"typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'local' => '"{{CANDIDATE}}"',
                    'relative' => '"typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'extension' => '"typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'auto - concatenate-and-compress' => [
                'auto', 'concatenate-and-compress',
                [
                    '!absolute' => '{{CANDIDATE}}',
                    '!relative' => '{{CANDIDATE}}',
                    '!extension' => '{{CANDIDATE}}',
                    'absolute' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'local' => '"/{{CANDIDATE}}"',
                    'relative' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'extension' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'absolute-with-host - concatenate-and-compress' => [
                'absolute-with-host', 'concatenate-and-compress',
                [
                    '!absolute' => '{{CANDIDATE}}',
                    '!relative' => '{{CANDIDATE}}',
                    '!extension' => '{{CANDIDATE}}',
                    'absolute' => '"http://localhost/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'local' => '"http://localhost/{{CANDIDATE}}"',
                    'relative' => '"http://localhost/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'extension' => '"http://localhost/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
            'absolute-without-host - concatenate-and-compress' => [
                'absolute-without-host', 'concatenate-and-compress',
                [
                    '!absolute' => '{{CANDIDATE}}',
                    '!relative' => '{{CANDIDATE}}',
                    '!extension' => '{{CANDIDATE}}',
                    'absolute' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'local' => '"/{{CANDIDATE}}"',
                    'relative' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'extension' => '"/typo3temp/assets/compressed/merged-[a-z0-9]+-[a-z0-9]+\.{{CANDIDATE-EXTENSION}}\?\d+"',
                    'external' => '"{{CANDIDATE}}"',
                ],
            ],
        ];
    }

    /**
     * @param string $absRefPrefixAspect
     * @param string $compressorAspect
     * @param array $expectations
     * @test
     * @dataProvider urisAreRenderedUsingAbsRefPrefixDataProvider
     */
    public function urisAreRenderedUsingAbsRefPrefix(string $absRefPrefixAspect, string $compressorAspect, array $expectations)
    {
        $response = $this->executeFrontendRequest(
            (new InternalRequest())->withQueryParameters([
                'id' => 1,
                'testAbsRefPrefix' => $absRefPrefixAspect,
                'testCompressor' => $compressorAspect,
            ])
        );
        $content = (string)$response->getBody();

        foreach ($expectations as $type => $expectation) {
            $shallExist = true;
            if (strpos($type, '!') === 0) {
                $shallExist = false;
                $type = substr($type, 1);
            }
            $candidates = array_map(
                function (string $candidateKey) {
                    return $this->resolvedResources[$candidateKey];
                },
                array_filter(
                    array_keys($this->resolvedResources),
                    function (string $candidateKey) use ($type) {
                        return strpos($candidateKey, $type) === 0;
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
                    self::assertRegExp(
                        '#' . $pattern . '#',
                        $content
                    );
                } else {
                    self::assertNotRegExp(
                        '#' . $pattern . '#',
                        $content
                    );
                }
            }
        }
    }

    /**
     * Adds TypoScript constants snippet to the existing template record
     *
     * @param int $pageId
     * @param string $constants
     * @param bool $append
     */
    protected function setTypoScriptConstantsToTemplateRecord(int $pageId, string $constants, bool $append = false)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_template');

        $template = $connection->select(['uid', 'constants'], 'sys_template', ['pid' => $pageId, 'root' => 1])->fetch();
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
     * @param array $constants
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
