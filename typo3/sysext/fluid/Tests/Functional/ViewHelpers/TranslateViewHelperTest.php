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
}
