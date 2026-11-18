..  include:: /Includes.rst.txt

..  _feature-111237-1795017600:

===================================================================
Feature: #111237 - Security voters for tables, fields and languages
===================================================================

See :issue:`111237`

Description
===========

Security voters, see :ref:`feature-111236-1794934515`, now decide on the
permissions backend groups grant: tables, exclude fields, values of
`authMode` fields, languages, page types, dashboard widgets, MFA providers
and custom options. The checks of the backend user object keep working.
The following preview shows the same checks asked from the authorization
checker; the subjects and scopes it uses are experimental, see the end of
this section:

..  code-block:: php

    use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
    use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
    use TYPO3\CMS\Core\Security\Authorization\Policy\ResourceIdentifier;
    use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
    use TYPO3\CMS\Core\Security\Authorization\SecurityRole;
    use TYPO3\CMS\Core\Security\Authorization\Subject\SchemaField;

    // Before
    $backendUser = $GLOBALS['BE_USER'];
    $isAdmin = $backendUser->isAdmin();
    $mayEditContent = $backendUser->check('tables_modify', 'tt_content');
    $mayEditHeader = $backendUser->check('non_exclude_fields', 'tt_content:header');
    $mayUseTextType = $backendUser->checkAuthMode('tt_content', 'CType', 'text');
    $mayEditGerman = $backendUser->checkLanguageAccess(1);
    $hasOption = $backendUser->check('custom_options', 'tx_news:publish');

    // After
    $isAdmin = $this->authorizationChecker->isGranted(SecurityRole::ADMIN->value);
    $schema = $this->tcaSchemaFactory->get('tt_content');
    $mayEditContent = $this->authorizationChecker->isGranted(Scope::WRITE, $schema);
    $mayEditHeader = $this->authorizationChecker->isGranted(
        Scope::WRITE,
        SchemaField::fromSchema($schema, 'header')
    );
    $mayUseTextType = $this->authorizationChecker->isGranted(
        Scope::WRITE,
        ResourceIdentifier::fieldValue('tt_content', 'CType', 'text')
    );
    $mayEditGerman = $this->authorizationChecker->isGranted(
        Scope::WRITE,
        ResourceIdentifier::language(1)
    );
    $hasOption = $this->authorizationChecker->isGranted(
        Scope::ACCESS,
        ResourceIdentifier::customOption('tx_news:publish')
    );

Terms
-----

Principal
    Who acts in a request: an authenticated backend user, a frontend user
    of a site, or the system. Authentication creates it once per request,
    and it does not change afterwards. Voters read its role
    (:php:`$principal->context->role`), its user id
    (:php:`$principal->context->id`), its group ids
    (:php:`$principal->context->groupIds`) and whether it is a backend user
    (:php:`$principal->isBackendUser()`). Voters decide for a principal,
    never for a user object such as :php:`$GLOBALS['BE_USER']`. A request
    without login has no principal.

Principal token
    Holds the principal of the current request in the token storage of
    `symfony/security-core` until the request ends, and nothing else.
    :php:`isGranted()` decides on it. It is never stored in the session
    and cannot be serialized, unlike an access token a client sends.

Role
    What a principal is, asked as attribute:
    :php:`isGranted(SecurityRole::ADMIN->value)`. The cases of
    :php:`\TYPO3\CMS\Core\Security\Authorization\SecurityRole` follow the
    role of the principal, e.g. an administrator holds `ROLE_USER`,
    `ROLE_BACKEND_USER` and `ROLE_ADMIN`. Every principal also holds one
    role per group, e.g. `ROLE_BACKEND_GROUP_3`.

Authorization checker
    The single entry point for authorization decisions:
    :php:`isGranted($attribute, $subject)` decides for the principal of the
    request, :php:`isGrantedForUser()` for any other principal. It asks the
    voters and combines their votes.

Attribute
    What is to be done, e.g. :php:`Scope::WRITE`, or which role is
    required, e.g. :php:`SecurityRole::ADMIN->value`.

Subject
    What it is done with, e.g. a :php:`TcaSchema` for a table, a
    :php:`SchemaField`, a :php:`SiteLanguage`, a backend module, a
    dashboard widget or a :php:`ResourceIdentifier`. Voters of extensions
    may decide on objects of their own.

Scope
    The attributes TYPO3 Core decides on for resources, the constants of
    :php:`\TYPO3\CMS\Core\Security\Authorization\Policy\Scope`: `read`,
    `write` for any change, i.e. creating, updating or deleting, and
    `access`. Grants do not tell creating, updating and deleting apart, so
    TYPO3 Core asks for `write` only.

Voter
    A service that decides on the attributes and subjects it supports: it
    grants, denies or abstains. Voters are registered with the
    :php:`#[AsSecurityVoter]` attribute and are the extension point of
    authorization: TYPO3 Core decides with voters of its own, and an
    extension adds a voter to restrict a decision or to decide on its own
    attributes and subjects. :php:`PrincipalVoter` is the base class:
    :php:`supports()` names what the voter decides on,
    :php:`voteOnPrincipal()` decides for the principal.

Decision strategy
    A decision is granted if at least one voter grants and none denies; if
    all voters abstain, it is denied. A voter of an extension can thus
    restrict any decision, but never grant what a voter of TYPO3 Core
    denies.

Grant
    A permission stored for a principal, such as a table listed in
    :sql:`be_groups.tables_modify`. Some decisions need no grant, e.g.
    fields that are no exclude fields.

Resource identifier
    The subject for a resource without an object of its own, named by a
    type and an identifier: :php:`ResourceIdentifier::pageType(254)`.
    Tables, fields, modules and widgets have an object of their own and
    are checked on it, not on a resource identifier. A language is checked
    on its :php:`SiteLanguage`, or on :php:`ResourceIdentifier::language()`
    where no site is at hand; both are decided alike, so a voter
    restricting a language supports both. An extension may use
    a type of its own, prefixed with its extension key, and decides on it
    with a voter of its own.

Schema field
    The subject for a field of a TCA table, as a field object of the
    schema does not know its table:
    :php:`SchemaField::fromSchema($schema, 'header')`.

Policy decision point
    The internal service the voters of TYPO3 Core ask whether a grant of
    the principal covers a scope on a resource. It knows where grants are
    stored, today the permission lists of the backend user and its groups;
    the voters know whether a grant is needed at all, e.g. that tables
    marked `adminOnly` are granted to administrators only, unless an
    explicit grant for the table exists. Extensions never call it, they
    write a voter.

The following decisions are available. Administrators, system maintainers
and the system are granted every decision based on a grant; tables marked
`adminOnly` are granted to administrators only, and to other backend users
by an explicit grant, see below. Principals of frontend users are denied
all of them. A sub-schema such as `tt_content.textmedia`, a field of it
and a value of such a field are denied, as grants and restricting voters
name the table: check the main schema. Tables, fields, modules and
widgets given as :php:`ResourceIdentifier` are denied as well, as they are
decided on their objects only.

..  list-table::
    :header-rows: 1

    *   -   Question
        -   Attribute and subject
        -   Granted by
        -   Former check
    *   -   Reading records of a table
        -   :php:`Scope::READ`, a :php:`TcaSchema`
        -   :sql:`be_groups.tables_select` or :sql:`tables_modify`
        -   :php:`check('tables_select', $table)`
    *   -   Creating, changing or deleting records of a table
        -   :php:`Scope::WRITE`, a :php:`TcaSchema`
        -   :sql:`be_groups.tables_modify`
        -   :php:`check('tables_modify', $table)`
    *   -   Editing an exclude field
        -   :php:`Scope::WRITE`, a :php:`SchemaField`
        -   :sql:`be_groups.non_exclude_fields`; fields that are no exclude
            fields are always granted
        -   :php:`check('non_exclude_fields', 'table:field')`
    *   -   Using a value of an `authMode` field
        -   :php:`Scope::WRITE`,
            :php:`ResourceIdentifier::fieldValue($table, $field, $value)`
        -   :sql:`be_groups.explicit_allowdeny`; a blank value and `--div--`
            are always granted, a value containing `:`, `|` or `,` is granted
            to administrators only
        -   :php:`checkAuthMode($table, $field, $value)`
    *   -   Editing a language
        -   :php:`Scope::WRITE`, a :php:`SiteLanguage` or
            :php:`ResourceIdentifier::language($languageId)`
        -   :sql:`be_users.allowed_languages`, :sql:`be_groups.allowed_languages`;
            all languages if empty, "all languages" (`-1`) always
        -   :php:`checkLanguageAccess($languageId)`
    *   -   Using a page type
        -   :php:`Scope::WRITE`, :php:`ResourceIdentifier::pageType($doktype)`
        -   :sql:`be_groups.pagetypes_select`
        -   :php:`check('pagetypes_select', $doktype)`
    *   -   Using a dashboard widget
        -   :php:`Scope::ACCESS`, a :php:`WidgetConfigurationInterface`
        -   :sql:`be_users.available_widgets`, :sql:`be_groups.availableWidgets`;
            widgets marked `adminOnly` are granted to administrators only
        -   :php:`check('available_widgets', $identifier)` and
            :php:`isAdminOnly()`
    *   -   Using an MFA provider
        -   :php:`Scope::ACCESS`, :php:`ResourceIdentifier::mfaProvider($identifier)`
        -   :sql:`be_users.mfa_providers`, :sql:`be_groups.mfa_providers`
        -   :php:`check('mfa_providers', $identifier)`
    *   -   Having a custom option
        -   :php:`Scope::ACCESS`,
            :php:`ResourceIdentifier::customOption('group:item')`
        -   :sql:`be_groups.custom_options`
        -   :php:`check('custom_options', 'group:item')`
    *   -   Being an administrator or a system maintainer
        -   :php:`SecurityRole::ADMIN->value`, :php:`SecurityRole::MAINTAINER->value`
        -   :sql:`be_users.admin`,
            :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers']`
        -   :php:`isAdmin()`, :php:`isSystemMaintainer()`
    *   -   Opening a backend module
        -   :php:`Scope::ACCESS`, a :php:`ModuleInterface`
        -   :sql:`be_users.userMods`, :sql:`be_groups.groupMods`, see
            :ref:`feature-111236-1794934515`
        -   :php:`check('modules', $identifier)`

Unlike :php:`check()`, an empty value or a value listing several items never
matches a grant; values of `authMode` fields are decided as
:php:`checkAuthMode()` does, see above, and an identifier without table,
field and value is denied, also for administrators.

The decisions are taken on the group data of the backend user object the
principal was built from, including groups added through the
:php:`AfterGroupsResolvedEvent`. Functional tests compare them with the
former checks for a set of users and groups. The principal of the request
is built at authentication: when code changes the group data of the
backend user object later in the request, e.g. to grant a table for one
operation, :php:`check()` sees the change, :php:`isGranted()` does not.

Some questions of :php:`check()` have no subject yet and stay with it:
tables without TCA, exclude fields of FlexForms, and modules and widgets
asked for by identifier instead of a :php:`ModuleInterface` or a widget
configuration. The :php:`DataHandler`
additionally restricts file records through its `checkModifyAccessList`
hooks, which no voter covers yet.

An extension restricts any of these decisions with a voter of its own that
denies, e.g. for a table:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Security/FrozenNewsVoter.php

    namespace MyVendor\MyExtension\Security;

    use Symfony\Component\Security\Core\Authorization\Voter\Vote;
    use TYPO3\CMS\Core\Attribute\AsSecurityVoter;
    use TYPO3\CMS\Core\Authentication\Principal;
    use TYPO3\CMS\Core\Schema\TcaSchema;
    use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
    use TYPO3\CMS\Core\Security\Authorization\SecurityRole;
    use TYPO3\CMS\Core\Security\Authorization\Voter\PrincipalVoter;

    /**
     * Only administrators may create, change or delete news records.
     */
    #[AsSecurityVoter]
    final class FrozenNewsVoter extends PrincipalVoter
    {
        protected function supports(string $attribute, mixed $subject): bool
        {
            return $subject instanceof TcaSchema
                && $subject->getName() === 'tx_news_domain_model_news'
                && $attribute === Scope::WRITE;
        }

        protected function voteOnPrincipal(
            string $attribute,
            mixed $subject,
            Principal $principal,
            ?Vote $vote = null,
        ): bool {
            return $this->hasRole($principal, SecurityRole::ADMIN);
        }
    }

An extension decides on a resource type of its own with a voter that
supports it; the voters of TYPO3 Core abstain on such types:

..  code-block:: php
    :caption: EXT:my_shop/Classes/Security/DiscountVoter.php

    namespace MyVendor\MyShop\Security;

    use Symfony\Component\Security\Core\Authorization\Voter\Vote;
    use TYPO3\CMS\Core\Attribute\AsSecurityVoter;
    use TYPO3\CMS\Core\Authentication\Principal;
    use TYPO3\CMS\Core\Security\Authorization\Policy\ResourceIdentifier;
    use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
    use TYPO3\CMS\Core\Security\Authorization\SecurityRole;
    use TYPO3\CMS\Core\Security\Authorization\Voter\PrincipalVoter;

    /**
     * isGranted(Scope::ACCESS, new ResourceIdentifier('tx_myshop_discount', 'staff'))
     */
    #[AsSecurityVoter]
    final class DiscountVoter extends PrincipalVoter
    {
        protected function supports(string $attribute, mixed $subject): bool
        {
            return $attribute === Scope::ACCESS
                && $subject instanceof ResourceIdentifier
                && $subject->type === 'tx_myshop_discount';
        }

        protected function voteOnPrincipal(
            string $attribute,
            mixed $subject,
            Principal $principal,
            ?Vote $vote = null,
        ): bool {
            return $subject->identifier !== 'staff'
                || $this->hasRole($principal, SecurityRole::ADMIN);
        }
    }

A voter cannot grant a table marked `adminOnly` to a backend user who is no
administrator, as the :php:`TableVoter` denies it, and grants of backend
groups never cover such a table. An extension that delegates such a table on
purpose, e.g. :sql:`be_users` to editors who manage other editors, implements
:php:`\TYPO3\CMS\Core\Security\Authorization\Policy\AdminOnlyTableGrantInterface`.
The policy decision point asks every implementation, and one grant suffices.
Voters of extensions still restrict the decision:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Security/EditorManagerGrant.php

    namespace MyVendor\MyExtension\Security;

    use MyVendor\MyExtension\Domain\EditorManagerRepository;
    use TYPO3\CMS\Core\Authentication\Principal;
    use TYPO3\CMS\Core\Security\Authorization\Policy\AdminOnlyTableGrantInterface;

    final readonly class EditorManagerGrant implements AdminOnlyTableGrantInterface
    {
        public function __construct(
            private EditorManagerRepository $editorManagers,
        ) {}

        public function isGranted(Principal $principal, string $scope, string $table): bool
        {
            return $table === 'be_users'
                && $this->editorManagers->isEditorManager($principal->context->id);
        }
    }

Granting a table grants every field of it that is no exclude field.
:sql:`be_users` marks no field as exclude field, so the grant above also
grants changing `admin`, `password` and `usergroup`. The extension restricts
the fields the grant is not meant for with a voter:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Security/EditorManagerFieldVoter.php

    namespace MyVendor\MyExtension\Security;

    use Symfony\Component\Security\Core\Authorization\Voter\Vote;
    use TYPO3\CMS\Core\Attribute\AsSecurityVoter;
    use TYPO3\CMS\Core\Authentication\Principal;
    use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
    use TYPO3\CMS\Core\Security\Authorization\SecurityRole;
    use TYPO3\CMS\Core\Security\Authorization\Subject\SchemaField;
    use TYPO3\CMS\Core\Security\Authorization\Voter\PrincipalVoter;

    /**
     * Only administrators may change these fields of backend users.
     */
    #[AsSecurityVoter]
    final class EditorManagerFieldVoter extends PrincipalVoter
    {
        private const array ADMIN_FIELDS = ['admin', 'TSconfig'];

        protected function supports(string $attribute, mixed $subject): bool
        {
            return $attribute === Scope::WRITE
                && $subject instanceof SchemaField
                && $subject->schema->getName() === 'be_users'
                && in_array($subject->field->getName(), self::ADMIN_FIELDS, true);
        }

        protected function voteOnPrincipal(
            string $attribute,
            mixed $subject,
            Principal $principal,
            ?Vote $vote = null,
        ): bool {
            return $this->hasRole($principal, SecurityRole::ADMIN);
        }
    }

The :php:`DataHandler` does not ask voters yet and keeps writing tables
marked `adminOnly` for administrators only.

Grants stored in a field an extension adds to :sql:`be_groups` are not yet
passed to voters. Until then, :php:`BackendUserAuthentication::check()`
keeps working for such fields.

The subjects :php:`\TYPO3\CMS\Core\Security\Authorization\Subject\SchemaField`
and :php:`\TYPO3\CMS\Core\Security\Authorization\Policy\ResourceIdentifier`,
:php:`\TYPO3\CMS\Core\Security\Authorization\Policy\AdminOnlyTableGrantInterface` and
the scopes `read` and `write` of
:php:`\TYPO3\CMS\Core\Security\Authorization\Policy\Scope` are experimental and
not yet public API: they may still change until the checks of
:php:`BackendUserAuthentication` are migrated to them. The voters of TYPO3 Core
and the policy decision point answering them are internal.

Impact
======

Permissions granted by backend groups can be checked with :php:`isGranted()`
and :php:`isGrantedForUser()` for every principal built from a backend user,
and extensions can restrict them with voters. The methods of
:php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication` keep working
and will delegate to the voters in a follow-up.

..  index:: Backend, PHP-API, ext:core, ext:dashboard
