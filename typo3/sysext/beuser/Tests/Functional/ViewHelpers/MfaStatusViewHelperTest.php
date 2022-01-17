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

namespace TYPO3\CMS\Beuser\Tests\Functional\ViewHelpers;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class MfaStatusViewHelperTest extends FunctionalTestCase
{
    use ProphecyTrait;

    protected $coreExtensionsToLoad = [
        'beuser',
    ];

    protected TemplateView $view;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/Fixtures/be_users_mfa.xml');

        // Default LANG prophecy just returns incoming value as label if calling ->sL()
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->loadSingleTableDescription(Argument::cetera())->willReturn(null);
        $languageServiceProphecy->sL(Argument::cetera())->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('bu', 'TYPO3\\CMS\\Beuser\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<bu:mfaStatus userUid="{userUid}"/>');
        $this->view = new TemplateView($context);
    }

    /**
     * @test
     */
    public function renderReturnsEmptyResultForInvalidUserUid(): void
    {
        self::assertEmpty($this->view->assign('userUid', 0)->render());
    }

    /**
     * @test
     */
    public function renderReturnsEmptyResultForUnknownUserUid(): void
    {
        self::assertEmpty($this->view->assign('userUid', 123)->render());
    }

    /**
     * @test
     */
    public function renderReturnsMfaEnabledLabel(): void
    {
        self::assertEquals(
            '<span class="label label-info">LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:mfaEnabled</span>',
            $this->view->assign('userUid', 1)->render()
        );
    }

    /**
     * @test
     */
    public function renderReturnsMfaLockedLabel(): void
    {
        self::assertEquals(
            '<span class="label label-warning">LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:lockedMfaProviders</span>',
            $this->view->assign('userUid', 2)->render()
        );
    }

    /**
     * @test
     */
    public function renderReturnsMfaLockedLabelOnMixedProviders(): void
    {
        self::assertEquals(
            '<span class="label label-warning">LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:lockedMfaProviders</span>',
            $this->view->assign('userUid', 3)->render()
        );
    }
}
