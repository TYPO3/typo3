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

namespace TYPO3\CMS\Core\Tests\Functional\Security\Authorization;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\UserAuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\Authorization\Policy\ResourceIdentifier;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\PrincipalToken;
use TYPO3\CMS\Core\Security\Authorization\PrincipalUser;
use TYPO3\CMS\Core\Security\Authorization\SecurityRole;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalContext;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestSecurityVoter\Voter\DiscountVoter;
use TYPO3Tests\TestSecurityVoter\Voter\FrozenRecordVoter;

final class AuthorizationCheckerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_security_voter',
    ];

    protected function tearDown(): void
    {
        $this->get(TokenStorageInterface::class)->setToken(null);
        parent::tearDown();
    }

    #[Test]
    public function accessIsDeniedWithoutPrincipal(): void
    {
        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted('test.read', 'record'));
    }

    #[Test]
    public function voterRegisteredViaAttributeGrantsAccess(): void
    {
        $this->authenticate(PrincipalRole::USER);

        self::assertTrue($this->get(AuthorizationCheckerInterface::class)->isGranted('test.read', 'record'));
    }

    #[Test]
    public function voterDecidesOnRolesOfThePrincipal(): void
    {
        $this->authenticate(PrincipalRole::USER);
        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted('test.write', 'record'));

        $this->authenticate(PrincipalRole::ADMIN);
        self::assertTrue($this->get(AuthorizationCheckerInterface::class)->isGranted('test.write', 'record'));
    }

    #[Test]
    public function voterDecidesOnResourceTypeOfItsOwn(): void
    {
        $staffDiscount = new ResourceIdentifier(DiscountVoter::RESOURCE_TYPE, 'staff');
        $this->authenticate(PrincipalRole::USER);
        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::ACCESS, $staffDiscount));

        $this->authenticate(PrincipalRole::ADMIN);
        self::assertTrue($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::ACCESS, $staffDiscount));
        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::WRITE, $staffDiscount));
    }

    #[Test]
    public function accessIsDeniedWhenAllVotersAbstain(): void
    {
        $this->authenticate(PrincipalRole::ADMIN);

        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted('test.unknown', 'record'));
    }

    #[Test]
    public function denyingVoterOverrulesGrantingVoter(): void
    {
        $this->authenticate(PrincipalRole::ADMIN);

        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted('test.write', 'frozen'));
    }

    #[Test]
    public function votersWithHigherPriorityAreAskedFirst(): void
    {
        $this->authenticate(PrincipalRole::ADMIN);
        $accessDecision = new AccessDecision();

        $this->get(AuthorizationCheckerInterface::class)->isGranted('test.write', 'frozen', $accessDecision);

        // The denial of the first voter ends the unanimous decision
        self::assertCount(1, $accessDecision->votes);
        self::assertSame(FrozenRecordVoter::class, $accessDecision->votes[0]->voter);
    }

    #[Test]
    public function rolesOfThePrincipalAreGranted(): void
    {
        $principal = new Principal(new PrincipalContext(PrincipalRole::ADMIN, 1, [4, 7]), 'backend', 'admin');
        $this->get(TokenStorageInterface::class)->setToken(new PrincipalToken($principal));
        $authorizationChecker = $this->get(AuthorizationCheckerInterface::class);

        self::assertTrue($authorizationChecker->isGranted('ROLE_ADMIN'));
        self::assertTrue($authorizationChecker->isGranted('ROLE_BACKEND_GROUP_7'));
        self::assertFalse($authorizationChecker->isGranted('ROLE_MAINTAINER'));
        self::assertFalse($authorizationChecker->isGranted('ROLE_FRONTEND_GROUP_7'));
    }

    #[Test]
    public function anonymousRequestIsDecidedWithoutRole(): void
    {
        $authorizationChecker = $this->get(AuthorizationCheckerInterface::class);

        self::assertTrue($authorizationChecker->isGranted(AuthenticatedVoter::PUBLIC_ACCESS));
        self::assertFalse($authorizationChecker->isGranted(AuthenticatedVoter::IS_AUTHENTICATED));
        self::assertFalse($authorizationChecker->isGranted(SecurityRole::USER->value));

        $principal = new Principal(new PrincipalContext(PrincipalRole::USER, 1), 'frontend', 'visitor');
        $this->get(TokenStorageInterface::class)->setToken(new PrincipalToken($principal));

        self::assertTrue($authorizationChecker->isGranted(AuthenticatedVoter::PUBLIC_ACCESS));
        self::assertTrue($authorizationChecker->isGranted(AuthenticatedVoter::IS_AUTHENTICATED));
        self::assertTrue($authorizationChecker->isGranted(SecurityRole::USER->value));
        self::assertTrue($authorizationChecker->isGranted(SecurityRole::FRONTEND_USER->value));
        self::assertFalse($authorizationChecker->isGranted(SecurityRole::BACKEND_USER->value));
    }

    #[Test]
    public function anotherPrincipalThanTheOneOfTheRequestCanBeChecked(): void
    {
        $this->authenticate(PrincipalRole::USER);
        $admin = new PrincipalUser(new Principal(new PrincipalContext(PrincipalRole::ADMIN, 2), 'backend', 'admin'));

        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted('test.write', 'record'));
        self::assertTrue($this->get(UserAuthorizationCheckerInterface::class)->isGrantedForUser($admin, 'test.write', 'record'));
    }

    private function authenticate(PrincipalRole $role): void
    {
        $principal = new Principal(new PrincipalContext($role, 1), 'backend', 'editor');
        $this->get(TokenStorageInterface::class)->setToken(new PrincipalToken($principal));
    }
}
