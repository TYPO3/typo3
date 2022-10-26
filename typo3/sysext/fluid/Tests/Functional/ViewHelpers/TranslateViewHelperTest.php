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

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\View\TemplateView;

class TranslateViewHelperTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['indexed_search'];

    /**
     * @test
     */
    public function renderThrowsExceptionIfNoKeyOrIdParameterIsGiven(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1351584844);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:translate />');
        (new TemplateView($context))->render();
    }

    /**
     * @test
     */
    public function renderThrowsExceptionInNonExtbaseContextWithoutExtensionName(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1639828178);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:translate key="key1" />');
        (new TemplateView($context))->render();
    }

    public function renderReturnsStringInNonExtbaseContextDataProvider(): array
    {
        return [
            'fallback to default attribute for not existing label' => [
                '<f:translate key="LLL:EXT:backend/Resources/Private/Language/locallang.xlf:iDoNotExist" default="myDefault" />',
                'myDefault',
            ],
            'fallback to default attribute for static label' => [
                '<f:translate key="static label" default="myDefault" />',
                'myDefault',
            ],
            'fallback to child for not existing label' => [
                '<f:translate key="LLL:EXT:backend/Resources/Private/Language/locallang.xlf:iDoNotExist">myDefault</f:translate>',
                'myDefault',
            ],
            'fallback to child for static label' => [
                '<f:translate key="static label">myDefault</f:translate>',
                'myDefault',
            ],
            'id and underscored extensionName given' => [
                '<f:translate key="form.legend" extensionName="indexed_search" />',
                'Search form',
            ],
            'key and underscored extensionName given' => [
                '<f:translate key="form.legend" extensionName="indexed_search" />',
                'Search form',
            ],
            'id and CamelCased extensionName given' => [
                '<f:translate key="form.legend" extensionName="IndexedSearch" />',
                'Search form',
            ],
            'key and CamelCased extensionName given' => [
                '<f:translate key="form.legend" extensionName="IndexedSearch" />',
                'Search form',
            ],
            'full LLL syntax for not existing label' => [
                '<f:translate key="LLL:EXT:backend/Resources/Private/Language/locallang.xlf:iDoNotExist" />',
                '',
            ],
            'full LLL syntax for existing label' => [
                '<f:translate key="LLL:EXT:indexed_search/Resources/Private/Language/locallang.xlf:form.legend" />',
                'Search form',
            ],
            'full LLL syntax for existing label with arguments without given arguments' => [
                '<f:translate key="LLL:EXT:backend/Resources/Private/Language/locallang.xlf:shortcut.title" />',
                '%s%s on page &quot;%s&quot; [%d]',
            ],
            'full LLL syntax for existing label with arguments with given arguments' => [
                '<f:translate key="LLL:EXT:backend/Resources/Private/Language/locallang.xlf:shortcut.title" arguments="{0: \"a\", 1: \"b\", 2: \"c\", 3: 13}"/>',
                'ab on page &quot;c&quot; [13]',
            ],
            'empty string on invalid extension' => [
                '<f:translate key="LLL:EXT:i_am_invalid/Resources/Private/Language/locallang.xlf:dummy" />',
                '',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderReturnsStringInNonExtbaseContextDataProvider
     */
    public function renderReturnsStringInNonExtbaseContext(string $template, string $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertSame($expected, (new TemplateView($context))->render());
    }

    public function renderReturnsStringInExtbaseContextDataProvider(): array
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
                '<f:translate key="validator.string.notvalid" extensionName="extbase" />',
                'A valid string is expected.',
            ],
            'key and extensionName given' => [
                '<f:translate key="validator.string.notvalid" extensionName="extbase" />',
                'A valid string is expected.',
            ],
            'full LLL syntax for not existing label' => [
                '<f:translate key="LLL:EXT:backend/Resources/Private/Language/locallang.xlf:iDoNotExist" />',
                '',
            ],
            'full LLL syntax for existing label' => [
                '<f:translate key="LLL:EXT:backend/Resources/Private/Language/locallang.xlf:login.header" />',
                'Login',
            ],
            'empty string on invalid extension' => [
                '<f:translate key="LLL:EXT:i_am_invalid/Resources/Private/Language/locallang.xlf:dummy" />',
                '',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderReturnsStringInExtbaseContextDataProvider
     */
    public function renderReturnsStringInExtbaseContext(string $template, string $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('backend');
        $serverRequest = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters);
        $extbaseRequest = (new Request($serverRequest));
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $context->setRequest($extbaseRequest);
        self::assertSame($expected, (new TemplateView($context))->render());
    }
}
