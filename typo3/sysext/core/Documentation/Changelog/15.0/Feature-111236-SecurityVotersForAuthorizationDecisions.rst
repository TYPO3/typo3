..  include:: /Includes.rst.txt

..  _feature-111236-1794934515:

==============================================================
Feature: #111236 - Security voters for authorization decisions
==============================================================

See :issue:`111236`

Description
===========

TYPO3 introduces security voters as the unified concept for authorization
decisions. Instead of asking a user object, such as
:php:`$GLOBALS['BE_USER']->isAdmin()` or :php:`->check('modules', ...)`,
code asks one question: is the current principal granted an attribute on a
subject?

..  code-block:: php

    use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
    use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;

    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly ModuleRegistry $moduleRegistry,
    ) {}

    public function canAccessLayoutModule(): bool
    {
        return $this->authorizationChecker->isGranted(
            Scope::ACCESS,
            $this->moduleRegistry->getModule('web_layout')
        );
    }

The authorization checker of `symfony/security-core` answers the question.
It asks every registered voter that supports the attribute and the subject.
Access is granted if at least one voter grants it and no voter denies it.
If all voters abstain, access is denied. A voter can therefore restrict
any decision, also one of TYPO3 Core or of another extension, but cannot
overrule the denial of another voter.

The authentication middlewares put the principal of the current request
into the token storage, so :php:`isGranted()` decides for the
authenticated backend or frontend user. Any other principal is checked
with :php:`UserAuthorizationCheckerInterface::isGrantedForUser()`.

Backend module access as first decision
---------------------------------------

The access to backend modules is decided by voters now. The new
:php:`\TYPO3\CMS\Backend\Security\Authorization\ModuleVoter` decides on the
access values of TYPO3 Core given in the `access` option of a module:

*   no access value: every backend user
*   `user`: administrators, and users granted the module or one of its
    aliases through their user record or one of their groups
*   `admin`: administrators
*   `systemMaintainer`: system maintainers

Frontend users are denied every module.

The workspace restriction given in the `workspaces` option of a module is
decided by a voter as well: with EXT:workspaces loaded, a module that is not
available in the current workspace of the backend user is denied.

The access value is no closed list. An extension may register a module with
an access value of its own, such as `'access' => 'projectLead'`, and decide
it with a voter that supports modules with this value. A module whose access
value no voter decides is not accessible.

:php:`\TYPO3\CMS\Backend\Module\ModuleProvider::accessGranted()` asks the
voters for every module and any backend user, so the module menu, the module
dispatching and every other place checking module access follow the voters,
and decide as :php:`isGranted()` does. The decision for `user` is taken on
the group data of the given backend user object, as before, the workspace
restriction on its current workspace, unless
:php:`$respectWorkspaceRestrictions` is set to :php:`false`.

Compared to the module access gates, the following details differ:

*   A backend user object without a user record (no uid) is granted modules
    without access value only.
*   :php:`BackendUserAuthentication::isSystemMaintainer()` evaluates the
    switch user mode of the user object itself instead of
    :php:`$GLOBALS['BE_USER']`.
*   A grant of any alias of a module grants the module.

Writing a voter
---------------

A voter is a service with the :php:`#[AsSecurityVoter]` attribute. Extending
:php:`\TYPO3\CMS\Core\Security\Authorization\Voter\PrincipalVoter` provides
the principal of the decision; requests without principal are denied.

..  code-block:: php
    :caption: EXT:my_extension/Classes/Security/InfoModuleVoter.php

    namespace MyVendor\MyExtension\Security;

    use Symfony\Component\Security\Core\Authorization\Voter\Vote;
    use TYPO3\CMS\Backend\Module\ModuleInterface;
    use TYPO3\CMS\Core\Attribute\AsSecurityVoter;
    use TYPO3\CMS\Core\Authentication\Principal;
    use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
    use TYPO3\CMS\Core\Security\Authorization\SecurityRole;
    use TYPO3\CMS\Core\Security\Authorization\Voter\PrincipalVoter;

    /**
     * Only administrators may open the page information overview, regardless of group grants.
     */
    #[AsSecurityVoter]
    final class InfoModuleVoter extends PrincipalVoter
    {
        protected function supports(string $attribute, mixed $subject): bool
        {
            return $attribute === Scope::ACCESS
                && $subject instanceof ModuleInterface
                && $subject->getIdentifier() === 'web_info_overview';
        }

        protected function voteOnPrincipal(
            string $attribute,
            mixed $subject,
            Principal $principal,
            ?Vote $vote = null,
        ): bool {
            if ($this->hasRole($principal, SecurityRole::ADMIN)) {
                return true;
            }
            $vote?->addReason('The page information overview is restricted to administrators.');
            return false;
        }
    }

The attribute takes an optional priority. Overriding
:php:`supportsAttribute()` and :php:`supportsType()` lets the checker skip
the voter without calling :php:`supports()`.

Roles
-----

Every principal holds roles, decided by the role voter of the security
component, e.g. :php:`isGranted(SecurityRole::ADMIN->value)`. The roles are
the cases of the new enum
:php:`\TYPO3\CMS\Core\Security\Authorization\SecurityRole`:

*   :php:`SecurityRole::USER` (`ROLE_USER`): the base role of the security
    component, held by every authenticated principal, backend and frontend
    users alike
*   :php:`SecurityRole::ADMIN`, :php:`SecurityRole::MAINTAINER`,
    :php:`SecurityRole::SYSTEM`: administrators, system maintainers and the
    system; the roles are cumulative, a system maintainer holds the role of
    an administrator as well
*   :php:`SecurityRole::BACKEND_USER`, :php:`SecurityRole::FRONTEND_USER`:
    every principal of the backend or of a frontend realm

In addition, every principal holds one role per group, such as
`ROLE_BACKEND_GROUP_3` or `ROLE_FRONTEND_GROUP_5`.

There is no role for anonymous requests, as they have no principal. The
attributes of the authenticated voter of the security component decide on
them. For frontend users, they correspond to the special groups of the
:sql:`fe_group` field of records:

*   "Show at any login" (`-2`): :php:`isGranted(SecurityRole::FRONTEND_USER->value)`
*   "Hide at login" (`-1`): not
    :php:`isGranted(AuthenticatedVoter::IS_AUTHENTICATED)`
*   any visitor: :php:`isGranted(AuthenticatedVoter::PUBLIC_ACCESS)`

Module access gates
-------------------

Module access gates, see :ref:`feature-109365-1774538611`, keep working
and are deprecated, see :ref:`deprecation-111236-1794990000`. A gate is
asked through a voter for an access value that is not known to TYPO3 Core,
and for an access value of TYPO3 Core whose gate an extension replaced.

Public API
----------

The following classes are public API:

*   :php:`\TYPO3\CMS\Core\Attribute\AsSecurityVoter`: registers a voter
*   :php:`\TYPO3\CMS\Core\Security\Authorization\Voter\PrincipalVoter`: base
    class of voters deciding for a principal
*   :php:`\TYPO3\CMS\Core\Security\Authorization\SecurityRole`: the roles of a
    principal
*   :php:`\TYPO3\CMS\Core\Security\Authorization\PrincipalUser`: a principal
    as user of the security component, e.g. for :php:`isGrantedForUser()`
*   :php:`\TYPO3\CMS\Core\Security\Authorization\Policy\Scope`: the attributes
    TYPO3 Core decides on
*   :php:`\TYPO3\CMS\Backend\Security\Authorization\ModuleVoter::ACCESS_VALUES`:
    the access values of modules decided by TYPO3 Core

The checker is used through the interfaces of the security component,
:php:`\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface`
and
:php:`\Symfony\Component\Security\Core\Authorization\UserAuthorizationCheckerInterface`.
All other new classes are internal. The principal a voter decides for,
:php:`\TYPO3\CMS\Core\Authentication\Principal`, is passed to
:php:`PrincipalVoter::voteOnPrincipal()`. Its read surface is public API:
the role, id and group ids of its context,
:php:`$principal->context->role`, :php:`$principal->context->id` and
:php:`$principal->context->groupIds`, and :php:`$principal->isBackendUser()`.
Creating a principal and its other properties are not part of the API yet,
as its identity is still bound to a local user record.

Impact
======

Authorization decisions can be taken for any principal with one API, and
extensions add rules with voters instead of XCLASSes or hooks. The access to
backend modules is the first decision taken this way; the permission checks
of :php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication` will
follow.

..  index:: Backend, PHP-API, ext:backend, ext:core
