<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Frontend\Tests\Functional\Rendering;

use PHPUnit\Util\PHP\AbstractPhpProcess;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Response;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class UriPrefixRenderingTest extends FunctionalTestCase
{
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
        'localLink' => '1',
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
        'localLink' => 'index.php?id=1',
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

    protected function setUp()
    {
        parent::setUp();
        $this->importDataSet('EXT:frontend/Tests/Functional/Fixtures/pages.xml');
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
        $content = $this->executeFrontendRequest(
            1,
            [
                'testAbsRefPrefix' => $absRefPrefixAspect,
                'testCompressor' => $compressorAspect
            ]
        );

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
                        preg_quote($pathInfo['extension'], '#'),
                    ],
                    $expectation
                );

                if ($shallExist) {
                    $this->assertRegExp(
                        '#' . $pattern . '#',
                        $content
                    );
                } else {
                    $this->assertNotRegExp(
                        '#' . $pattern . '#',
                        $content
                    );
                }
            }
        }
    }

    /**
     * Executes frontend request by invoking PHP sub-process.
     *
     * @param int $pageId
     * @param array $queryArguments
     * @return string
     */
    protected function executeFrontendRequest(int $pageId, array $queryArguments = []): string
    {
        $query = array_merge(
            $queryArguments,
            ['id' => (int)$pageId]
        );
        $arguments = [
            'documentRoot' => $this->instancePath,
            'requestUrl' => 'http://localhost/?' . http_build_query($query),
        ];

        $template = new \Text_Template(TYPO3_PATH_PACKAGES . 'typo3/testing-framework/Resources/Core/Functional/Fixtures/Frontend/request.tpl');
        $template->setVar(
            [
                'arguments' => var_export($arguments, true),
                'originalRoot' => ORIGINAL_ROOT,
                'vendorPath' => TYPO3_PATH_PACKAGES
            ]
        );

        $php = AbstractPhpProcess::factory();
        $response = $php->runJob($template->render());
        $result = json_decode($response['stdout'], true);

        if ($result === null) {
            $this->fail('Frontend Response is empty');
        }

        if ($result['status'] === Response::STATUS_Failure) {
            $this->fail('Frontend Response has failure:' . LF . $result['error']);
        }

        return $result['content'];
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
            $this->fail('Cannot find root template on page with id: "' . $pageId . '"');
        }
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
