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

namespace TYPO3\CMS\Dashboard\Tests\Functional\Security\Authorization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use TYPO3\CMS\Core\Authentication\Pipeline\LegacyUserPrincipalFactory;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\Authorization\Policy\ResourceIdentifier;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\PrincipalToken;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalContext;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfiguration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Compares the decisions of the widget voter with the permission check of
 * the WidgetRegistry, for every registered widget.
 */
final class WidgetVoterTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['dashboard'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WidgetAccess.csv');
    }

    protected function tearDown(): void
    {
        $this->get(TokenStorageInterface::class)->setToken(null);
        parent::tearDown();
    }

    public static function usersDataProvider(): \Generator
    {
        yield 'admin' => [1];
        yield 'editor granted an admin only widget' => [2];
        yield 'editor without grants' => [3];
    }

    #[Test]
    #[DataProvider('usersDataProvider')]
    public function widgetVoterMatchesWidgetRegistry(int $userId): void
    {
        $backendUser = $this->setUpBackendUser($userId);
        $principal = $this->get(LegacyUserPrincipalFactory::class)->fromUser($backendUser, Realm::backend());
        $this->get(TokenStorageInterface::class)->setToken(new PrincipalToken($principal));
        $widgetRegistry = $this->get(WidgetRegistry::class);
        $availableWidgets = $widgetRegistry->getAvailableWidgets();
        $authorizationChecker = $this->get(AuthorizationCheckerInterface::class);
        foreach ($widgetRegistry->getAllWidgets() as $identifier => $widget) {
            self::assertSame(isset($availableWidgets[$identifier]), $authorizationChecker->isGranted(Scope::ACCESS, $widget), $identifier);
        }
    }

    #[Test]
    public function adminOnlyWidgetIsNotGrantedByAGrant(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        $principal = $this->get(LegacyUserPrincipalFactory::class)->fromUser($backendUser, Realm::backend());
        $this->get(TokenStorageInterface::class)->setToken(new PrincipalToken($principal));
        $authorizationChecker = $this->get(AuthorizationCheckerInterface::class);

        self::assertTrue($authorizationChecker->isGranted(Scope::ACCESS, $this->createWidget('sysLogErrors', false)));
        self::assertFalse($authorizationChecker->isGranted(Scope::ACCESS, $this->createWidget('latestBeLogins', true)));
        self::assertFalse($authorizationChecker->isGranted(Scope::ACCESS, ResourceIdentifier::widget('latestBeLogins')));
    }

    #[Test]
    public function widgetIsNotGrantedToAFrontendPrincipal(): void
    {
        $principal = new Principal(new PrincipalContext(PrincipalRole::USER, 2), 'frontend', 'editor');
        $this->get(TokenStorageInterface::class)->setToken(new PrincipalToken($principal));

        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::ACCESS, $this->createWidget('sysLogErrors', false)));
    }

    private function createWidget(string $identifier, bool $adminOnly): WidgetConfiguration
    {
        return new WidgetConfiguration($identifier, 'service', ['general'], 'title', 'description', 'icon', 'small', 'small', $adminOnly);
    }
}
