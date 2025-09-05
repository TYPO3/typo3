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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class MfaStatusViewHelperTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'beuser',
    ];

    protected TemplateView $view;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users_mfa.csv');

        $mockLanguageService = $this->getMockBuilder(LanguageService::class)->disableOriginalConstructor()->getMock();
        $mockLanguageService->expects($this->any())->method('sL')->willReturnArgument(0);
        $GLOBALS['LANG'] = $mockLanguageService;

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('bu', 'TYPO3\\CMS\\Beuser\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<bu:mfaStatus userUid="{userUid}"/>');
        $this->view = new TemplateView($context);
    }

    #[Test]
    public function renderReturnsEmptyResultForInvalidUserUid(): void
    {
        self::assertEmpty($this->view->assign('userUid', 0)->render());
    }

    #[Test]
    public function renderReturnsEmptyResultForUnknownUserUid(): void
    {
        self::assertEmpty($this->view->assign('userUid', 123)->render());
    }

    #[Test]
    public function renderReturnsMfaEnabledLabel(): void
    {
        self::assertEquals(
            '<span class="badge badge-info">LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:mfaEnabled</span>',
            $this->view->assign('userUid', 1)->render()
        );
    }

    #[Test]
    public function renderReturnsMfaLockedLabel(): void
    {
        self::assertEquals(
            '<span class="badge badge-warning">LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:lockedMfaProviders</span>',
            $this->view->assign('userUid', 2)->render()
        );
    }

    #[Test]
    public function renderReturnsMfaLockedLabelOnMixedProviders(): void
    {
        self::assertEquals(
            '<span class="badge badge-warning">LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:lockedMfaProviders</span>',
            $this->view->assign('userUid', 3)->render()
        );
    }
}
