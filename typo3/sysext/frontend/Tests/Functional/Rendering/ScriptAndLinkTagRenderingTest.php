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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ScriptAndLinkTagRenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_resource_rendering',
    ];

    /**
     * @var string[]
     */
    private array $definedResources = [
        'forceOnTopCSS'         => 'EXT:test_resource_rendering/Resources/Public/forceOnTop.css',
        'forceOnTopJS'          => 'EXT:test_resource_rendering/Resources/Public/forceOnTop.js',
        'forceOnTopCSSLib'      => 'EXT:test_resource_rendering/Resources/Public/forceOnTopLib.css',
        'forceOnTopJSLib'       => 'EXT:test_resource_rendering/Resources/Public/forceOnTopLib.js',
        'forceOnTopJSLibFooter' => 'EXT:test_resource_rendering/Resources/Public/forceOnTopLibFooter.js',

        'alternateCSS'          => 'EXT:test_resource_rendering/Resources/Public/alternate.css',
        'alternateJS'           => 'EXT:test_resource_rendering/Resources/Public/alternate.js',
        'alternateCSSLib'       => 'EXT:test_resource_rendering/Resources/Public/alternateLib.css',
        'alternateJSLib'        => 'EXT:test_resource_rendering/Resources/Public/alternateLib.js',
        'alternateJSLibFooter'  => 'EXT:test_resource_rendering/Resources/Public/alternateLibFooter.js',

        'dataCSS'               => 'EXT:test_resource_rendering/Resources/Public/data.css',
        'dataJS'                => 'EXT:test_resource_rendering/Resources/Public/data.js',
        'dataCSSLib'            => 'EXT:test_resource_rendering/Resources/Public/dataLib.css',
        'dataJSLib'             => 'EXT:test_resource_rendering/Resources/Public/dataLib.js',
        'dataJSLibFooter'       => 'EXT:test_resource_rendering/Resources/Public/dataLibFooter.js',
    ];

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
            ['EXT:frontend/Tests/Functional/Rendering/Fixtures/ScriptAndLinkTagRenderingTest.typoscript']
        );
        $this->setTypoScriptConstantsToTemplateRecord(
            1,
            $this->compileTypoScriptConstants($this->definedResources)
        );
    }

    /**
     * This tests a few possible variants of link/script TypoScript rendering
     * in one go, to save some frontend request calls.
     */
    #[Test]
    public function scriptAndLinkTagsRemoveUnneededAdditionalParameters(): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withQueryParameters([
                'id' => 1,
            ])
        );
        $content = (string)$response->getBody();

        $expectations = [
            'forceOnTopCSS'         => '<link rel="stylesheet" href="/typo3conf/ext/test_resource_rendering/Resources/Public/forceOnTop.css" media="all">',
            'forceOnTopCSSLib'      => '<link rel="stylesheet" href="/typo3conf/ext/test_resource_rendering/Resources/Public/forceOnTopLib.css" media="all">',
            'forceOnTopJS'          => '<script src="/typo3conf/ext/test_resource_rendering/Resources/Public/forceOnTop.js"></script>',
            'forceOnTopJSLib'       => '<script src="/typo3conf/ext/test_resource_rendering/Resources/Public/forceOnTopLib.js"></script>',
            'forceOnTopJSLibFooter' => '<script src="/typo3conf/ext/test_resource_rendering/Resources/Public/forceOnTopLibFooter.js"></script>',

            'alternateCSS'          => '<link rel="alternate stylesheet" href="/typo3conf/ext/test_resource_rendering/Resources/Public/alternate.css" media="print" title="Dummy">',
            'alternateCSSLib'       => '<link rel="alternate stylesheet" href="/typo3conf/ext/test_resource_rendering/Resources/Public/alternateLib.css" media="print" title="Dummy">',
            'alternateJS'           => '<script src="/typo3conf/ext/test_resource_rendering/Resources/Public/alternate.js" type="text/plain" defer="defer" nomodule="nomodule" integrity="4711" crossorigin="example.com"></script>',
            'alternateJSLib'        => '<script src="/typo3conf/ext/test_resource_rendering/Resources/Public/alternateLib.js" type="text/plain" defer="defer" nomodule="nomodule" integrity="4711" crossorigin="example.com"></script>',
            'alternateJSLibFooter'  => '<script src="/typo3conf/ext/test_resource_rendering/Resources/Public/alternateLibFooter.js" type="text/plain" defer="defer" nomodule="nomodule" integrity="4711" crossorigin="example.com"></script>',

            'dataCSS'               => '<link rel="stylesheet" href="/typo3conf/ext/test_resource_rendering/Resources/Public/data.css" media="all" somethingcustom="someValue" data-attribute="value">',
            'dataCSSLib'            => '<link rel="stylesheet" href="/typo3conf/ext/test_resource_rendering/Resources/Public/dataLib.css" media="all" somethingcustom="someValue" data-attribute="value">',
            'dataJS'                => '<script src="/typo3conf/ext/test_resource_rendering/Resources/Public/data.js" data-attribute="value"></script>',
            'dataJSLib'             => '<script src="/typo3conf/ext/test_resource_rendering/Resources/Public/dataLib.js" somethingcustom="someValue" data-attribute="value"></script>',
            'dataJSLibFooter'       => '<script src="/typo3conf/ext/test_resource_rendering/Resources/Public/dataLibFooter.js" somethingcustom="someValue" data-attribute="value"></script>',
        ];

        foreach ($expectations as $expectationHtml) {
            self::assertStringContainsString($expectationHtml, $content);
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
