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

namespace TYPO3\CMS\Backend\Tests\Functional\ViewHelpers\Mfa;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class IfHasStateViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    protected StandaloneView $view;

    protected function setUp(): void
    {
        parent::setUp();
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->getRenderingContext()->getViewHelperResolver()->addNamespace('be', 'TYPO3\\CMS\\Backend\\ViewHelpers');
        $this->view->setTemplatePathAndFilename('EXT:backend/Tests/Functional/ViewHelpers/Fixtures/Mfa/IfHasStateViewHelper.html');
        $this->view->assign('provider', $this->get(MfaProviderRegistry::class)->getProvider('totp'));
    }

    /**
     * @test
     */
    public function renderReturnsInactive(): void
    {
        $GLOBALS['BE_USER'] = $this->getBackendUser();
        $result = $this->view->render();

        self::assertStringNotContainsString('isActive', $result);
        self::assertStringContainsString('isInactive', $result);
        self::assertStringNotContainsString('isLocked', $result);
        self::assertStringNotContainsString('isUnlocked', $result);
    }
    /**
     * @test
     */
    public function renderReturnsActive(): void
    {
        $GLOBALS['BE_USER'] = $this->getBackendUser(true);
        $result = $this->view->render();

        self::assertStringContainsString('isActive', $result);
        self::assertStringNotContainsString('isInactive', $result);
        self::assertStringNotContainsString('isLocked', $result);
        self::assertStringContainsString('isUnlocked', $result);
    }
    /**
     * @test
     */
    public function renderReturnsLocked(): void
    {
        $GLOBALS['BE_USER'] = $this->getBackendUser(true, true);
        $result = $this->view->render();

        self::assertStringContainsString('isActive', $result);
        self::assertStringNotContainsString('isInactive', $result);
        self::assertStringContainsString('isLocked', $result);
        self::assertStringNotContainsString('isUnlocked', $result);
    }

    protected function getBackendUser(bool $activeProvider = false, bool $lockedProvider = false): BackendUserAuthentication
    {
        $backendUser = new BackendUserAuthentication();
        $mfa = [
            'totp' => [
                'secret' => 'KRMVATZTJFZUC53FONXW2ZJB',
            ],
        ];

        if ($activeProvider) {
            $mfa['totp']['active'] = true;
        }
        if ($lockedProvider) {
            $mfa['totp']['attempts'] = 3;
        }

        $backendUser->user['mfa'] = json_encode($mfa);
        return $backendUser;
    }
}
