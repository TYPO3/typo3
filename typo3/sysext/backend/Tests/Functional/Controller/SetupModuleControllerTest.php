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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\SetupModuleController;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SetupModuleControllerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
    }

    #[Test]
    public function mainActionRendersUserSettingsFields(): void
    {
        $html = $this->renderSetupModule();
        self::assertStringContainsString('user_settings__emailMeAtLogin', $html);
        self::assertStringContainsString('[be_users__password]', $html);
        self::assertStringContainsString('user_settings__password2', $html);
    }

    #[Test]
    public function mainActionDoesNotRenderFieldDisabledByUserTsConfig(): void
    {
        $this->setUserTsConfig('setup.fields.emailMeAtLogin.disabled = 1');
        $html = $this->renderSetupModule();
        self::assertStringNotContainsString('user_settings__emailMeAtLogin', $html);
        self::assertStringContainsString('user_settings__titleLen', $html);
    }

    #[Test]
    public function mainActionDisablingPasswordAlsoHidesPasswordConfirmation(): void
    {
        $this->setUserTsConfig('setup.fields.password.disabled = 1');
        $html = $this->renderSetupModule();
        self::assertStringNotContainsString('[be_users__password]', $html);
        self::assertStringNotContainsString('user_settings__password2', $html);
    }

    #[Test]
    public function mainActionRendersOverriddenFieldsAsDisabled(): void
    {
        $this->setUserTsConfig("setup.override.emailMeAtLogin = 1\nsetup.override.startModule = dashboard");
        $html = $this->renderSetupModule();
        self::assertStringContainsString('disabled', $this->extractFormElementTag($html, 'user_settings__emailMeAtLogin'));
        self::assertStringContainsString('disabled', $this->extractFormElementTag($html, 'user_settings__startModule'));
        self::assertStringNotContainsString('disabled', $this->extractFormElementTag($html, 'user_settings__titleLen'));
    }

    private function extractFormElementTag(string $html, string $tcaFieldName): string
    {
        $matchCount = preg_match(
            '/<(?:input|select)\b[^>]*\[' . preg_quote($tcaFieldName, '/') . '\][^>]*>/',
            $html,
            $matches
        );
        self::assertSame(1, $matchCount, 'Form element for field "' . $tcaFieldName . '" not found');
        return $matches[0];
    }

    private function setUserTsConfig(string $tsConfig): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('be_users')
            ->update('be_users', ['TSconfig' => $tsConfig], ['uid' => 1]);
    }

    private function renderSetupModule(): string
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $normalizedParams = self::createStub(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn('/');
        $normalizedParams->method('getRequestUri')->willReturn('/typo3/setup');
        $request = new ServerRequest('https://example.com/typo3/setup')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('route', new Route('/module/user/setup', ['packageName' => 'typo3/cms-backend', '_identifier' => 'user_setup']));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->get(SetupModuleController::class)->mainAction($request);
        return (string)$response->getBody();
    }
}
