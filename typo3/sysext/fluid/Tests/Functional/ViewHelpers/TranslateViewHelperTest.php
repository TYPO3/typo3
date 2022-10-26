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

use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

class TranslateViewHelperTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function renderThrowsExceptionIfNoKeyOrIdParameterIsGiven(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1351584844);

        $view = new StandaloneView();
        $view->setTemplateSource('<f:translate />');
        $view->render();
    }

    /**
     * @test
     */
    public function renderReturnsStringForGivenKey(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:translate key="foo">hello world</f:translate>');
        self::assertSame('hello world', $view->render());
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
     */
    public function renderReturnsStringForGivenId(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:translate id="foo">hello world</f:translate>');
        self::assertSame('hello world', $view->render());
    }

    /**
     * @test
     */
    public function renderReturnsDefaultIfNoTranslationIsFound(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:translate id="foo" default="default" />');
        self::assertSame('default', $view->render());
    }

    /**
     * @test
     */
    public function renderReturnsTranslatedKey(): void
    {
        $this->setUpBackendUserFromFixture(1);
        $view = new StandaloneView();
        $view->setTemplateSource('<f:translate key="LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack" />');
        self::assertSame('Go back', $view->render());
    }

    /**
     * @test
     */
    public function renderReturnsNullOnInvalidExtension(): void
    {
        $this->setUpBackendUserFromFixture(1);
        $view = new StandaloneView();
        $view->setTemplateSource('<f:translate key="LLL:EXT:invalid/Resources/Private/Language/locallang.xlf:dummy" />');
        self::assertNull($view->render());
    }
}
