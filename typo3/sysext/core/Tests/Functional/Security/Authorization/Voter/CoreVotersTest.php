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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Pipeline\LegacyUserPrincipalFactory;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Security\Authorization\Policy\ResourceIdentifier;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\PrincipalToken;
use TYPO3\CMS\Core\Security\Authorization\Subject\SchemaField;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalContext;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Compares the decisions of the voters with the legacy permission checks
 * of BackendUserAuthentication.
 */
final class CoreVotersTest extends FunctionalTestCase
{
    private const array USERS = [1, 2, 3, 4, 5];
    private const array TABLES = ['pages', 'tt_content', 'sys_file', 'sys_template', 'sys_category', 'be_users', 'be_groups'];
    private const array FIELDS = ['tt_content:header', 'tt_content:bodytext', 'tt_content:hidden', 'pages:title', 'pages:hidden'];
    private const array LANGUAGES = [-1, 0, 1, 2, 3, 5];
    private const array PAGE_TYPES = [1, 3, 254];

    protected array $coreExtensionsToLoad = ['dashboard'];

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

    public static function usersDataProvider(): \Generator
    {
        foreach (self::USERS as $userId) {
            yield 'user ' . $userId => [$userId];
        }
    }

    #[Test]
    #[DataProvider('usersDataProvider')]
    public function tableVoterMatchesLegacyChecks(int $userId): void
    {
        $backendUser = $this->authenticate($userId);
        $authorizationChecker = $this->get(AuthorizationCheckerInterface::class);
        foreach (self::TABLES as $table) {
            $schema = $this->get(TcaSchemaFactory::class)->get($table);
            $isGrantedForAdminOnly = !$schema->hasCapability(TcaSchemaCapability::AccessAdminOnly) || $backendUser->isAdmin();
            $legacyRead = $backendUser->check('tables_select', $table) && $isGrantedForAdminOnly;
            $legacyWrite = $backendUser->check('tables_modify', $table) && $isGrantedForAdminOnly;
            self::assertSame($legacyRead, $authorizationChecker->isGranted(Scope::READ, $schema), 'read ' . $table);
            self::assertSame($legacyWrite, $authorizationChecker->isGranted(Scope::WRITE, $schema), 'write ' . $table);
        }
    }

    #[Test]
    #[DataProvider('usersDataProvider')]
    public function fieldVoterMatchesLegacyChecks(int $userId): void
    {
        $backendUser = $this->authenticate($userId);
        $authorizationChecker = $this->get(AuthorizationCheckerInterface::class);
        foreach (self::FIELDS as $field) {
            [$table, $fieldName] = explode(':', $field);
            $subject = SchemaField::fromSchema($this->get(TcaSchemaFactory::class)->get($table), $fieldName);
            $legacy = !$subject->field->supportsAccessControl() || $backendUser->check('non_exclude_fields', $field);
            self::assertSame($legacy, $authorizationChecker->isGranted(Scope::WRITE, $subject), $field);
        }
    }

    #[Test]
    #[DataProvider('usersDataProvider')]
    public function languageVoterMatchesLegacyChecks(int $userId): void
    {
        $backendUser = $this->authenticate($userId);
        $authorizationChecker = $this->get(AuthorizationCheckerInterface::class);
        foreach (self::LANGUAGES as $languageId) {
            $language = new SiteLanguage($languageId, 'en_US', new Uri('/'), []);
            self::assertSame($backendUser->checkLanguageAccess($language), $authorizationChecker->isGranted(Scope::WRITE, $language), 'language ' . $languageId);
            self::assertSame($backendUser->checkLanguageAccess($languageId), $authorizationChecker->isGranted(Scope::WRITE, ResourceIdentifier::language($languageId)), 'language id ' . $languageId);
        }
    }

    #[Test]
    #[DataProvider('usersDataProvider')]
    public function resourceVoterMatchesLegacyChecks(int $userId): void
    {
        $backendUser = $this->authenticate($userId);
        $authorizationChecker = $this->get(AuthorizationCheckerInterface::class);
        foreach (self::PAGE_TYPES as $doktype) {
            self::assertSame(
                $backendUser->check('pagetypes_select', (string)$doktype),
                $authorizationChecker->isGranted(Scope::WRITE, ResourceIdentifier::pageType($doktype)),
                'page type ' . $doktype
            );
        }
    }

    #[Test]
    #[DataProvider('usersDataProvider')]
    public function fieldValueDecisionMatchesCheckAuthMode(int $userId): void
    {
        $backendUser = $this->authenticate($userId);
        $authorizationChecker = $this->get(AuthorizationCheckerInterface::class);
        foreach (['text', 'textpic', '', '--div--', 'text:pic', 'text|pic', 'text,textpic'] as $value) {
            self::assertSame(
                $backendUser->checkAuthMode('tt_content', 'CType', $value),
                $authorizationChecker->isGranted(Scope::WRITE, ResourceIdentifier::fieldValue('tt_content', 'CType', $value)),
                'CType "' . $value . '"'
            );
        }
    }

    public static function resourcesWithObjectDataProvider(): \Generator
    {
        yield 'table' => [ResourceIdentifier::table('be_users')];
        yield 'field' => [ResourceIdentifier::field('tt_content', 'hidden')];
        yield 'module' => [ResourceIdentifier::module('web_layout')];
        yield 'widget' => [ResourceIdentifier::widget('sysLogErrors')];
    }

    #[Test]
    #[DataProvider('resourcesWithObjectDataProvider')]
    public function resourcesWithObjectAreNotDecidedAsResourceIdentifier(ResourceIdentifier $resource): void
    {
        // Decided on the TcaSchema, SchemaField, module or widget only, so that
        // "adminOnly", exclude fields and module access values apply
        $this->authenticate(1);

        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::WRITE, $resource));
        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::ACCESS, $resource));
    }

    public static function decisionsDataProvider(): \Generator
    {
        yield 'admin only table for admin' => [1, Scope::WRITE, 'be_groups', null, true];
        yield 'admin only table is not granted by a grant' => [2, Scope::READ, 'be_users', null, false];
        yield 'granted table' => [2, Scope::WRITE, 'tt_content', null, true];
        yield 'table of a subgroup' => [2, Scope::READ, 'pages', null, true];
        yield 'table without grant' => [5, Scope::READ, 'pages', null, false];
        yield 'field without access control' => [5, Scope::WRITE, 'tt_content', 'header', true];
        yield 'granted exclude field' => [2, Scope::WRITE, 'pages', 'title', true];
        yield 'exclude field without grant' => [2, Scope::WRITE, 'tt_content', 'hidden', false];
        yield 'sub-schema is not decided' => [1, Scope::WRITE, 'tt_content.text', null, false];
        yield 'field of a sub-schema is not decided' => [1, Scope::WRITE, 'tt_content.text', 'header', false];
    }

    #[Test]
    #[DataProvider('decisionsDataProvider')]
    public function decisions(int $userId, string $scope, string $table, ?string $field, bool $expected): void
    {
        $this->authenticate($userId);
        $schema = $this->get(TcaSchemaFactory::class)->get($table);

        self::assertSame(
            $expected,
            $this->get(AuthorizationCheckerInterface::class)->isGranted($scope, $field === null ? $schema : SchemaField::fromSchema($schema, $field))
        );
    }

    public static function malformedFieldValuesDataProvider(): \Generator
    {
        yield 'no field and value' => ['tt_content'];
        yield 'no value' => ['tt_content:CType'];
        yield 'no table' => [':CType:text'];
        yield 'no field' => ['tt_content::text'];
        yield 'table of a sub-schema' => ['tt_content.text:CType:html'];
    }

    #[Test]
    #[DataProvider('malformedFieldValuesDataProvider')]
    public function malformedFieldValueIsDenied(string $identifier): void
    {
        $this->authenticate(1);

        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::WRITE, new ResourceIdentifier(ResourceIdentifier::FIELD_VALUE, $identifier)));
    }

    #[Test]
    public function frontendPrincipalIsDeniedEveryDecision(): void
    {
        $principal = new Principal(new PrincipalContext(PrincipalRole::USER, 2), 'frontend', 'editor');
        $this->get(TokenStorageInterface::class)->setToken(new PrincipalToken($principal));
        $schema = $this->get(TcaSchemaFactory::class)->get('tt_content');
        $authorizationChecker = $this->get(AuthorizationCheckerInterface::class);

        self::assertFalse($authorizationChecker->isGranted(Scope::READ, $schema));
        self::assertFalse($authorizationChecker->isGranted(Scope::WRITE, SchemaField::fromSchema($schema, 'header')));
        self::assertFalse($authorizationChecker->isGranted(Scope::WRITE, ResourceIdentifier::fieldValue('tt_content', 'CType', '')));
        self::assertFalse($authorizationChecker->isGranted(Scope::WRITE, ResourceIdentifier::language(-1)));
    }

    #[Test]
    public function unauthenticatedRequestIsDenied(): void
    {
        $schema = $this->get(TcaSchemaFactory::class)->get('pages');

        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::READ, $schema));
        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::WRITE, SchemaField::fromSchema($schema, 'title')));
    }

    private function authenticate(int $userId): BackendUserAuthentication
    {
        $backendUser = $this->setUpBackendUser($userId);
        $principal = $this->get(LegacyUserPrincipalFactory::class)->fromUser($backendUser, Realm::backend());
        $this->get(TokenStorageInterface::class)->setToken(new PrincipalToken($principal));
        return $backendUser;
    }
}
