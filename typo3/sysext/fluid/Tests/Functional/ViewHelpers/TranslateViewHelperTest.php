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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\View\TemplateView;

final class TranslateViewHelperTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/test_translate',
    ];

    #[Test]
    public function renderThrowsExceptionIfNoKeyOrIdParameterIsGiven(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1351584844);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:translate />');
        (new TemplateView($context))->render();
    }

    #[Test]
    public function renderThrowsExceptionIfOnlyDefaultValueIsGiven(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1351584844);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:translate default="myDefault" />');
        (new TemplateView($context))->render();
    }

    #[Test]
    public function renderThrowsExceptionInNonExtbaseContextWithoutExtensionNameAndDefaultValue(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1639828178);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:translate key="key1" />');
        (new TemplateView($context))->render();
    }

    public static function renderReturnsStringInNonExtbaseContextDataProvider(): array
    {
        return [
            'fallback to default attribute for not existing label' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:iDoNotExist" default="myDefault" />',
                'myDefault',
            ],
            'fallback to default attribute for static label' => [
                '<f:translate key="static label" default="myDefault" />',
                'myDefault',
            ],
            'fallback to child for not existing label' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:iDoNotExist">myDefault</f:translate>',
                'myDefault',
            ],
            'fallback to child for static label' => [
                '<f:translate key="static label">myDefault</f:translate>',
                'myDefault',
            ],
            'id and underscored extensionName given' => [
                '<f:translate id="form.legend" extensionName="test_translate" />',
                'Search form',
            ],
            'key and underscored extensionName given' => [
                '<f:translate key="form.legend" extensionName="test_translate" />',
                'Search form',
            ],
            'id and CamelCased extensionName given' => [
                '<f:translate id="form.legend" extensionName="TestTranslate" />',
                'Search form',
            ],
            'key and CamelCased extensionName given' => [
                '<f:translate key="form.legend" extensionName="TestTranslate" />',
                'Search form',
            ],
            'valid id and extensionName with default value given' => [
                '<f:translate id="form.legend" extensionName="TestTranslate" default="myDefault" />',
                'Search form',
            ],
            'invalid id and extensionName given with default value given' => [
                '<f:translate key="invalid" extensionName="TestTranslate" default="myDefault" />',
                'myDefault',
            ],
            'full LLL syntax for not existing label' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:iDoNotExist" />',
                '',
            ],
            'full LLL syntax for existing label' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:form.legend" />',
                'Search form',
            ],
            'full LLL syntax for existing label with arguments without given arguments' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:shortcut.title" />',
                '%s%s on page &quot;%s&quot; [%d]',
            ],
            'full LLL syntax for existing label with arguments with given arguments' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:shortcut.title" arguments="{0: \"a\", 1: \"b\", 2: \"c\", 3: 13}"/>',
                'ab on page &quot;c&quot; [13]',
            ],
            'empty string on invalid extension' => [
                '<f:translate key="LLL:EXT:i_am_invalid/Resources/Private/Language/locallang.xlf:dummy" />',
                '',
            ],
            'languageKey fallback to default when key is not localized to de' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:not.localized.to.de" languageKey="de" />',
                'EN label',
            ],
            'languageKey de when key is localized to de' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:localized.to.de" languageKey="de" />',
                'DE label',
            ],
            'languageKey de when key is not localized to de_at' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:localized.to.de" languageKey="de_at" />',
                'DE label',
            ],
            'languageKey de_at when key is localized to de_at' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:localized.to.de_at" languageKey="de_at" />',
                'DE_AT label',
            ],
            'languageKey en when key is overridden in en' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:localized.to.en" />',
                'EN label override en',
            ],
            'fallback to default, integer-based attribute for not existing label' => [
                '<f:for each="{4711:\'4712\'}" as="i" iteration="iterator" key="k"><f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:iDoNotExist">{k}</f:translate></f:for>',
                '4711',
            ],
        ];
    }

    #[DataProvider('renderReturnsStringInNonExtbaseContextDataProvider')]
    #[Test]
    public function renderReturnsStringInNonExtbaseContext(string $template, string $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertSame($expected, (new TemplateView($context))->render());
    }

    public static function fallbackChainInNonExtbaseContextDataProvider(): array
    {
        return [
            'languageKey fallback to default when key is not localized to en' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:not.localized.to.en" />',
                'EN label',
            ],
            'languageKey fallback to default when key is not localized to de' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:not.localized.to.de" languageKey="de" />',
                'EN label',
            ],
            'languageKey de when key is localized to de' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:localized.to.de" languageKey="de" />',
                'DE label',
            ],
            'languageKey de when key is not localized to de_ch' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:localized.to.de" />',
                'DE label',
            ],
            'languageKey de_at when key is localized to de_ch' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:localized.to.de_ch" />',
                'DE-CH label',
            ],
            'key + extensionName: languageKey fallback to default when key is not localized to de' => [
                '<f:translate extensionName="test_translate" key="not.localized.to.de" languageKey="de" />',
                'EN label',
            ],
            'key + extensionName: languageKey de when key is localized to de' => [
                '<f:translate extensionName="test_translate" key="localized.to.de" languageKey="de" />',
                'DE label',
            ],
            'key + extensionName: fallback to "de" when key is not localized to de_ch' => [
                '<f:translate extensionName="test_translate" key="localized.to.de" />',
                'DE label',
            ],
            'key + extensionName: find "de_ch" when key is localized to de_ch' => [
                '<f:translate extensionName="test_translate" key="localized.to.de_ch" />',
                'DE-CH label',
            ],
        ];
    }

    /**
     * Analyzes that the frontend request can resolve the locale from the frontend request,
     * both LLL: prefix and extensionName + id combinations.
     */
    #[DataProvider('fallbackChainInNonExtbaseContextDataProvider')]
    #[Test]
    public function renderInNonExtbaseContextHandlesLocaleFromFrontendRequest(string $template, string $expected): void
    {
        $request = new ServerRequest();
        $request = $request
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('language', new SiteLanguage(0, 'de_CH.utf8', new Uri('https://example.ch/'), []));
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertSame($expected, (new TemplateView($context))->render());
    }

    #[Test]
    public function renderInNonExtbaseContextHandlesLocaleObjectAsLanguageKey(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:localized.to.de" languageKey="{myLocale}" />');
        $templateView = new TemplateView($context);
        $templateView->assign('myLocale', (new Locales())->createLocale('de'));
        self::assertSame('DE label', $templateView->render());
    }

    #[Test]
    public function renderInNonExtbaseContextHandlesLocaleObjectAsLanguageKeyWithFallback(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:localized.to.de" languageKey="{myLocale}" />');
        $templateView = new TemplateView($context);
        $templateView->assign('myLocale', (new Locales())->createLocale('de_at'));
        self::assertSame('DE label', $templateView->render());
    }

    #[Test]
    public function renderInNonExtbaseContextHandlesLocaleObjectAsLanguageKeyWithoutFallback(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:localized.to.de_at" languageKey="{myLocale}" />');
        $templateView = new TemplateView($context);
        $templateView->assign('myLocale', (new Locales())->createLocale('de_at'));
        self::assertSame('DE_AT label', $templateView->render());
    }

    public static function renderReturnsStringInExtbaseContextDataProvider(): array
    {
        return [
            'key given for not existing label, fallback to child' => [
                '<f:translate key="foo">hello world</f:translate>',
                'hello world',
            ],
            'id given for not existing label, fallback to child' => [
                '<f:translate id="foo">hello world</f:translate>',
                'hello world',
            ],
            'fallback to default attribute for not existing label' => [
                '<f:translate key="foo" default="myDefault" />',
                'myDefault',
            ],
            'id given with existing label' => [
                '<f:translate id="login.header" />',
                'Login',
            ],
            'key given with existing label' => [
                '<f:translate key="login.header" />',
                'Login',
            ],
            'key given with existing label and arguments without given arguments' => [
                '<f:translate key="shortcut.title" />',
                '%s%s on page &quot;%s&quot; [%d]',
            ],
            'key given with existing label and arguments with given arguments' => [
                '<f:translate key="shortcut.title" arguments="{0: \"a\", 1: \"b\", 2: \"c\", 3: 13}" />',
                'ab on page &quot;c&quot; [13]',
            ],
            'id and extensionName given' => [
                '<f:translate key="validator.string.notvalid" extensionName="test_translate" />',
                'A valid string is expected.',
            ],
            'key and extensionName given' => [
                '<f:translate key="validator.string.notvalid" extensionName="test_translate" />',
                'A valid string is expected.',
            ],
            'full LLL syntax for not existing label' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:iDoNotExist" />',
                '',
            ],
            'full LLL syntax for existing label' => [
                '<f:translate key="LLL:EXT:test_translate/Resources/Private/Language/locallang.xlf:login.header" />',
                'Login',
            ],
            'empty string on invalid extension' => [
                '<f:translate key="LLL:EXT:i_am_invalid/Resources/Private/Language/locallang.xlf:dummy" />',
                '',
            ],
            'languageKey fallback to default when key is not localized to de' => [
                '<f:translate key="not.localized.to.de" languageKey="de" />',
                'EN label',
            ],
            'languageKey de when key is localized to de' => [
                '<f:translate key="localized.to.de" languageKey="de" />',
                'DE label',
            ],
            'fallback to de when key is not localized to de_at with explicit languageKey given' => [
                '<f:translate key="localized.to.de" languageKey="de_at" />',
                'DE label',
            ],
            'fallback to de when key is not localized to de_at without explicit languageKey given' => [
                '<f:translate key="localized.to.de" />',
                'DE label',
            ],
            'use direct "de_AT" label when key is localized to de_at with explicit languageKey given' => [
                '<f:translate key="localized.to.de_at" languageKey="de_at" />',
                'DE_AT label',
            ],
            'use direct "de_AT" label when key is localized to de-AT with explicit languageKey given' => [
                '<f:translate key="localized.to.de_at" languageKey="de-AT" />',
                'DE_AT label',
            ],
            'use direct "de_AT" label when key is localized to de_AT with explicit languageKey given' => [
                '<f:translate key="localized.to.de_at" languageKey="de_AT" />',
                'DE_AT label',
            ],
            'use direct "de_AT" label when key is localized to de_at without explicit languageKey given' => [
                '<f:translate key="localized.to.de_at" />',
                'DE_AT label',
            ],
            'id given for not existing label, fallback to child with integer based tag content' => [
                '<f:for each="{4711:\'4712\'}" as="i" iteration="iterator" key="k"><f:translate id="foo">{k}</f:translate></f:for>',
                '4711',
            ],
        ];
    }

    #[DataProvider('renderReturnsStringInExtbaseContextDataProvider')]
    #[Test]
    public function renderReturnsStringInExtbaseContext(string $template, string $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['BE_USER']->user['lang'] = 'de-AT';
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('test_translate');
        $serverRequest = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters)->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $extbaseRequest = (new Request($serverRequest));
        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertSame($expected, (new TemplateView($context))->render());
    }

    #[Test]
    public function renderInExtbaseContextHandlesLocaleObjectAsLanguageKey(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('test_translate');
        $serverRequest = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters)->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $extbaseRequest = (new Request($serverRequest));
        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource('<f:translate key="localized.to.de" languageKey="{myLocale}" />');
        $templateView = new TemplateView($context);
        $templateView->assign('myLocale', (new Locales())->createLocale('de'));
        self::assertSame('DE label', $templateView->render());
    }

    #[Test]
    public function renderInExtbaseContextHandlesLocaleObjectAsLanguageKeyWithFallback(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('test_translate');
        $serverRequest = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters)->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $extbaseRequest = (new Request($serverRequest));
        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource('<f:translate key="localized.to.de" languageKey="{myLocale}" />');
        $templateView = new TemplateView($context);
        $templateView->assign('myLocale', (new Locales())->createLocale('de_at'));
        self::assertSame('DE label', $templateView->render());
    }

    #[Test]
    public function renderInExtbaseContextHandlesLocaleObjectAsLanguageKeyWithoutFallback(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('test_translate');
        $serverRequest = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters)->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $extbaseRequest = (new Request($serverRequest));
        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource('<f:translate key="localized.to.de_at" languageKey="{myLocale}" />');
        $templateView = new TemplateView($context);
        $templateView->assign('myLocale', (new Locales())->createLocale('de_at'));
        self::assertSame('DE_AT label', $templateView->render());
    }

    #[Test]
    public function renderInExtbaseFrontendContextHandlesLabelOverrideWithTypoScriptInDefaultLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
        );
        (new ConnectionPool())->getConnectionForTable('sys_template')->insert('sys_template', [
            'pid' => 1,
            'root' => 1,
            'clear' => 1,
            'config' => <<<EOT
page = PAGE
page.10 = EXTBASEPLUGIN
page.10 {
    extensionName = TestTranslate
    pluginName = Test
}
plugin.tx_testtranslate_test._LOCAL_LANG.default.localized\.to\.de = TypoScript default label
EOT
        ]);
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(2));
        self::assertStringContainsString('TypoScript default label', (string)$response->getBody());

    }

    #[Test]
    public function renderInExtbaseFrontendContextHandlesLabelOverrideWithTypoScriptInLocalizedPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN']),
            ]
        );
        (new ConnectionPool())->getConnectionForTable('sys_template')->insert('sys_template', [
            'pid' => 1,
            'root' => 1,
            'clear' => 1,
            'config' => <<<EOT
page = PAGE
page.10 = EXTBASEPLUGIN
page.10 {
    extensionName = TestTranslate
    pluginName = Test
}
plugin.tx_testtranslate_test._LOCAL_LANG.de-DE.localized\.to\.de = TypoScript de label
EOT
        ]);
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(2)->withLanguageId(1));
        self::assertStringContainsString('TypoScript de label', (string)$response->getBody());

    }
}
