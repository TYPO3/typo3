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

namespace TYPO3\CMS\Core\Tests\Functional\Security\Authorization\Policy;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Pipeline\LegacyUserPrincipalFactory;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\Authorization\LegacyBackendUserRegistry;
use TYPO3\CMS\Core\Security\Authorization\Policy\PolicyDecisionPointInterface;
use TYPO3\CMS\Core\Security\Authorization\Policy\ResourceIdentifier;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalContext;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LegacyGroupDataPolicyDecisionPointTest extends FunctionalTestCase
{
    private const array USERS = [1, 2, 3, 4, 5];

    protected array $coreExtensionsToLoad = ['dashboard'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LegacyGroupData.csv');
    }

    /**
     * Each case names a decision and the legacy check it has to agree with.
     */
    public static function goldenCasesDataProvider(): \Generator
    {
        $cases = [];
        foreach (['pages', 'tt_content', 'tx_news', 'sys_file', 'sys_template', 'sys_category', 'sys_note', 'sys_log'] as $table) {
            $cases['read ' . $table] = [Scope::READ, ResourceIdentifier::table($table), 'check', ['tables_select', $table]];
            $cases['write ' . $table] = [Scope::WRITE, ResourceIdentifier::table($table), 'check', ['tables_modify', $table]];
        }
        foreach (['tt_content:header', 'tt_content:bodytext', 'pages:title', 'tt_content:hidden'] as $field) {
            [$table, $fieldName] = explode(':', $field);
            $cases['field ' . $field] = [Scope::WRITE, ResourceIdentifier::field($table, $fieldName), 'check', ['non_exclude_fields', $field]];
        }
        foreach (['text', 'textpic'] as $value) {
            $cases['field value CType ' . $value] = [Scope::WRITE, ResourceIdentifier::fieldValue('tt_content', 'CType', $value), 'checkAuthMode', ['tt_content', 'CType', $value]];
        }
        foreach ([-1, 0, 1, 2, 3, 5] as $languageId) {
            $cases['language ' . $languageId] = [Scope::WRITE, ResourceIdentifier::language($languageId), 'checkLanguageAccess', [$languageId]];
        }
        foreach ([1, 3, 199, 254] as $doktype) {
            $cases['page type ' . $doktype] = [Scope::WRITE, ResourceIdentifier::pageType($doktype), 'check', ['pagetypes_select', (string)$doktype]];
        }
        foreach (['web_layout', 'web_list', 'news_admin', 'dashboard', 'web_info', 'file_list', 'tools_toolsmaintenance'] as $module) {
            $cases['module ' . $module] = [Scope::ACCESS, ResourceIdentifier::module($module), 'check', ['modules', $module]];
        }
        foreach (['sysLogErrors', 'unknownWidget'] as $widget) {
            $cases['widget ' . $widget] = [Scope::ACCESS, ResourceIdentifier::widget($widget), 'check', ['available_widgets', $widget]];
        }
        foreach (['totp', 'recovery-codes', 'webauthn'] as $provider) {
            $cases['mfa provider ' . $provider] = [Scope::ACCESS, ResourceIdentifier::mfaProvider($provider), 'check', ['mfa_providers', $provider]];
        }
        foreach (['group1:item1', 'group1:item2'] as $option) {
            $cases['custom option ' . $option] = [Scope::ACCESS, ResourceIdentifier::customOption($option), 'check', ['custom_options', $option]];
        }
        foreach (self::USERS as $userId) {
            foreach ($cases as $name => $case) {
                yield 'user ' . $userId . ', ' . $name => [$userId, ...$case];
            }
        }
    }

    #[Test]
    #[DataProvider('goldenCasesDataProvider')]
    public function decisionMatchesLegacyBackendUserCheck(int $userId, string $scope, ResourceIdentifier $resource, string $legacyMethod, array $legacyArguments): void
    {
        $backendUser = $this->setUpBackendUser($userId);

        self::assertSame(
            $backendUser->$legacyMethod(...$legacyArguments),
            $this->get(PolicyDecisionPointInterface::class)->decide($this->createPrincipal($backendUser), $scope, $resource)
        );
    }

    public static function decisionsDataProvider(): \Generator
    {
        yield 'admin is granted without grant' => [1, Scope::WRITE, ResourceIdentifier::table('be_users'), true];
        yield 'grant of the group' => [2, Scope::WRITE, ResourceIdentifier::table('tt_content'), true];
        yield 'grant of a subgroup' => [2, Scope::WRITE, ResourceIdentifier::table('tx_news'), true];
        yield 'modify grant implies read' => [2, Scope::READ, ResourceIdentifier::table('tx_news'), true];
        yield 'read grant does not imply write' => [2, Scope::WRITE, ResourceIdentifier::table('pages'), false];
        yield 'module of the user record' => [2, Scope::ACCESS, ResourceIdentifier::module('dashboard'), true];
        yield 'language of a subgroup' => [2, Scope::WRITE, ResourceIdentifier::language(2), true];
        yield 'language not granted' => [2, Scope::WRITE, ResourceIdentifier::language(3), false];
        yield 'hidden group grants nothing' => [3, Scope::WRITE, ResourceIdentifier::table('sys_file'), false];
        yield 'deleted group grants nothing' => [3, Scope::WRITE, ResourceIdentifier::table('sys_template'), false];
        yield 'empty language list grants all languages' => [3, Scope::WRITE, ResourceIdentifier::language(7), true];
        yield 'padded list values are trimmed' => [4, Scope::READ, ResourceIdentifier::table('sys_note'), true];
        yield 'all languages marker is always granted' => [4, Scope::WRITE, ResourceIdentifier::language(-1), true];
        yield 'user without groups' => [5, Scope::READ, ResourceIdentifier::table('pages'), false];
        yield 'unknown scope' => [2, 'publish', ResourceIdentifier::table('tt_content'), false];
        yield 'unknown scope for admin' => [1, 'publish', ResourceIdentifier::table('tt_content'), false];
        yield 'unknown resource type' => [2, Scope::ACCESS, new ResourceIdentifier('route', 'web_layout'), false];
        yield 'empty identifier' => [5, Scope::READ, ResourceIdentifier::table(''), false];
        yield 'identifier listing two values' => [2, Scope::ACCESS, ResourceIdentifier::module('web_layout,web_list'), false];
    }

    #[Test]
    #[DataProvider('decisionsDataProvider')]
    public function decide(int $userId, string $scope, ResourceIdentifier $resource, bool $expected): void
    {
        $principal = $this->createPrincipal($this->setUpBackendUser($userId));

        self::assertSame($expected, $this->get(PolicyDecisionPointInterface::class)->decide($principal, $scope, $resource));
    }

    #[Test]
    public function supportsResourceTypesAndScopesWithGrants(): void
    {
        $policyDecisionPoint = $this->get(PolicyDecisionPointInterface::class);

        self::assertTrue($policyDecisionPoint->supports(Scope::WRITE, ResourceIdentifier::pageType(254)));
        self::assertFalse($policyDecisionPoint->supports(Scope::ACCESS, ResourceIdentifier::pageType(254)));
        self::assertFalse($policyDecisionPoint->supports(Scope::ACCESS, new ResourceIdentifier('tx_test_discount', 'staff')));
    }

    #[Test]
    public function frontendPrincipalIsDenied(): void
    {
        $principal = new Principal(new PrincipalContext(PrincipalRole::USER, 2), 'frontend', 'editor');

        self::assertFalse($this->get(PolicyDecisionPointInterface::class)->decide($principal, Scope::READ, ResourceIdentifier::table('pages')));
    }

    #[Test]
    public function principalNotBuiltFromAUserObjectIsDenied(): void
    {
        // The group data is only taken from a principal registered for a
        // user object, e.g. by LegacyUserPrincipalFactory
        $backendUser = $this->setUpBackendUser(2);
        $principal = new Principal(
            new PrincipalContext($backendUser->getRole(), (int)$backendUser->getUserId(), $backendUser->userGroupsUID),
            'backend',
            (string)$backendUser->getUserName(),
        );

        self::assertFalse($this->get(PolicyDecisionPointInterface::class)->decide($principal, Scope::ACCESS, ResourceIdentifier::module('web_layout')));
    }

    #[Test]
    public function changedGroupDataIsDecidedForANewPrincipalOnly(): void
    {
        $backendUser = $this->setUpBackendUser(5);
        $principal = $this->createPrincipal($backendUser);
        $policyDecisionPoint = $this->get(PolicyDecisionPointInterface::class);
        self::assertFalse($policyDecisionPoint->decide($principal, Scope::WRITE, ResourceIdentifier::table('sys_note')));

        $backendUser->groupData['tables_modify'] = 'sys_note';

        self::assertFalse($policyDecisionPoint->decide($principal, Scope::WRITE, ResourceIdentifier::table('sys_note')));
        self::assertTrue($policyDecisionPoint->decide($this->createPrincipal($backendUser), Scope::WRITE, ResourceIdentifier::table('sys_note')));
    }

    #[Test]
    public function principalIsRegisteredOnce(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        $principal = $this->createPrincipal($backendUser);

        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1795720926);
        $this->get(LegacyBackendUserRegistry::class)->register($principal, $backendUser);
    }

    private function createPrincipal(BackendUserAuthentication $backendUser): Principal
    {
        return $this->get(LegacyUserPrincipalFactory::class)->fromUser($backendUser, Realm::backend());
    }
}
