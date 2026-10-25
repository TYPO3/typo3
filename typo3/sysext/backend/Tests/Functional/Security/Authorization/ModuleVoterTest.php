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

namespace TYPO3\CMS\Backend\Tests\Functional\Security\Authorization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\CMS\Backend\Security\Authorization\ModuleVoter;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Pipeline\LegacyUserPrincipalFactory;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\PrincipalToken;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Compares the decisions of the module voter with the access checks of
 * BackendUserAuthentication the access gates of TYPO3 Core are based on,
 * for every registered module.
 */
final class ModuleVoterTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['dashboard', 'info', 'setup'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ModuleAccess.csv');
    }

    protected function tearDown(): void
    {
        $this->get(TokenStorageInterface::class)->setToken(null);
        parent::tearDown();
    }

    public static function usersDataProvider(): \Generator
    {
        yield 'admin' => [1];
        yield 'editor with groups and subgroups' => [2];
        yield 'editor with a hidden group' => [3];
        yield 'editor without grants' => [4];
    }

    #[Test]
    #[DataProvider('usersDataProvider')]
    public function moduleVoterMatchesLegacyChecksForThePrincipalOfTheRequest(int $userId): void
    {
        $backendUser = $this->setUpBackendUser($userId);
        $principal = $this->get(LegacyUserPrincipalFactory::class)->fromUser($backendUser, Realm::backend());
        $this->get(TokenStorageInterface::class)->setToken(new PrincipalToken($principal));
        $authorizationChecker = $this->get(AuthorizationCheckerInterface::class);
        $granted = 0;
        foreach ($this->getModulesDecidedByTheVoter() as $identifier => $module) {
            $expected = $this->isGrantedByLegacyChecks($module, $backendUser);
            $granted += (int)$expected;
            self::assertSame($expected, $authorizationChecker->isGranted(Scope::ACCESS, $module), $identifier);
        }
        self::assertGreaterThan(0, $granted);
    }

    #[Test]
    #[DataProvider('usersDataProvider')]
    public function moduleProviderMatchesLegacyChecks(int $userId): void
    {
        $backendUser = $this->setUpBackendUser($userId);
        $moduleProvider = $this->get(ModuleProvider::class);
        foreach ($this->getModulesDecidedByTheVoter() as $identifier => $module) {
            self::assertSame(
                $this->isGrantedByLegacyChecks($module, $backendUser),
                $moduleProvider->accessGranted($identifier, $backendUser, false),
                $identifier
            );
        }
    }

    /**
     * @return array<string, ModuleInterface>
     */
    private function getModulesDecidedByTheVoter(): array
    {
        return array_filter(
            $this->get(ModuleRegistry::class)->getModules(),
            static fn(ModuleInterface $module): bool => in_array($module->getAccess(), ModuleVoter::ACCESS_VALUES, true)
        );
    }

    private function isGrantedByLegacyChecks(ModuleInterface $module, BackendUserAuthentication $user): bool
    {
        if ($module->getAccess() === '') {
            return true;
        }
        if ($module->getAccess() === 'admin') {
            return $user->isAdmin();
        }
        if ($module->getAccess() === 'systemMaintainer') {
            return $user->isSystemMaintainer();
        }
        $alias = array_search($module->getIdentifier(), $this->get(ModuleRegistry::class)->getModuleAliases(), true);
        return $user->isAdmin()
            || $user->check('modules', $module->getIdentifier())
            || ($alias !== false && $user->check('modules', $alias));
    }
}
