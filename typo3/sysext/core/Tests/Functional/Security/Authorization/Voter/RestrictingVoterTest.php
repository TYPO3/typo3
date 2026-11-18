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

namespace TYPO3\CMS\Core\Tests\Functional\Security\Authorization\Voter;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use TYPO3\CMS\Core\Authentication\Pipeline\LegacyUserPrincipalFactory;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Security\Authorization\Policy\ResourceIdentifier;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\PrincipalToken;
use TYPO3\CMS\Core\Security\Authorization\Subject\SchemaField;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestSecurityVoter\Grant\BackendUserManagerGrant;
use TYPO3Tests\TestSecurityVoter\Voter\FrozenContentVoter;

/**
 * A voter of an extension restricts a decision of TYPO3 Core on the
 * attribute TYPO3 Core asks for.
 */
final class RestrictingVoterTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['dashboard'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_security_voter',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Policy/Fixtures/LegacyGroupData.csv');
    }

    protected function tearDown(): void
    {
        $this->get(TokenStorageInterface::class)->setToken(null);
        parent::tearDown();
    }

    #[Test]
    public function voterOfAnExtensionRestrictsChangesGrantedByAGroup(): void
    {
        $schema = $this->get(TcaSchemaFactory::class)->get(FrozenContentVoter::TABLE);
        $authorizationChecker = $this->get(AuthorizationCheckerInterface::class);

        // The editor is granted tt_content through tables_modify of a group
        $this->authenticate(2);
        self::assertFalse($authorizationChecker->isGranted(Scope::WRITE, $schema));
        self::assertTrue($authorizationChecker->isGranted(Scope::READ, $schema));

        $this->authenticate(1);
        self::assertTrue($authorizationChecker->isGranted(Scope::WRITE, $schema));
    }

    #[Test]
    public function adminOnlyTableIsGrantedByAnExplicitGrantOnly(): void
    {
        $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
        $authorizationChecker = $this->get(AuthorizationCheckerInterface::class);

        $this->authenticate(BackendUserManagerGrant::USER_ID);
        self::assertTrue($authorizationChecker->isGranted(Scope::READ, $tcaSchemaFactory->get('be_users')));
        self::assertTrue($authorizationChecker->isGranted(Scope::WRITE, $tcaSchemaFactory->get('be_users')));
        self::assertFalse($authorizationChecker->isGranted(Scope::READ, $tcaSchemaFactory->get('be_groups')));

        // Another user who is no administrator is not granted be_users
        $this->authenticate(4);
        self::assertFalse($authorizationChecker->isGranted(Scope::WRITE, $tcaSchemaFactory->get('be_users')));
    }

    #[Test]
    public function voterOfAnExtensionCannotGrantAnAdminOnlyTable(): void
    {
        $schema = $this->get(TcaSchemaFactory::class)->get('be_groups');
        $authorizationChecker = $this->get(AuthorizationCheckerInterface::class);

        $this->authenticate(2);
        self::assertFalse($authorizationChecker->isGranted(Scope::WRITE, $schema));

        $this->authenticate(1);
        self::assertTrue($authorizationChecker->isGranted(Scope::WRITE, $schema));
    }

    #[Test]
    public function voterOfAnExtensionCannotGrantWhatTypo3CoreDoesNotDecide(): void
    {
        $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
        $authorizationChecker = $this->get(AuthorizationCheckerInterface::class);

        // The editor is granted tt_content, the voter grants everything
        $this->authenticate(2);
        self::assertFalse($authorizationChecker->isGranted(Scope::READ, $tcaSchemaFactory->get('be_users')->getSubSchema('0')));
        self::assertFalse($authorizationChecker->isGranted(Scope::READ, $tcaSchemaFactory->get('tt_content')->getSubSchema('text')));
        self::assertFalse($authorizationChecker->isGranted(Scope::WRITE, SchemaField::fromSchema($tcaSchemaFactory->get('tt_content')->getSubSchema('text'), 'header')));
        self::assertFalse($authorizationChecker->isGranted(Scope::WRITE, ResourceIdentifier::table('be_users')));
        self::assertFalse($authorizationChecker->isGranted(Scope::WRITE, ResourceIdentifier::field('tt_content', 'hidden')));
        self::assertFalse($authorizationChecker->isGranted(Scope::ACCESS, ResourceIdentifier::module('tools_toolsmaintenance')));
        self::assertFalse($authorizationChecker->isGranted(Scope::ACCESS, ResourceIdentifier::widget('sysLogErrors')));
    }

    #[Test]
    public function grantOfAnAdminOnlyTableIsDecidedOnItsSchemaOnly(): void
    {
        $this->authenticate(BackendUserManagerGrant::USER_ID);

        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::WRITE, ResourceIdentifier::adminOnlyTable('be_users')));
    }

    private function authenticate(int $userId): void
    {
        $principal = $this->get(LegacyUserPrincipalFactory::class)->fromUser($this->setUpBackendUser($userId), Realm::backend());
        $this->get(TokenStorageInterface::class)->setToken(new PrincipalToken($principal));
    }
}
