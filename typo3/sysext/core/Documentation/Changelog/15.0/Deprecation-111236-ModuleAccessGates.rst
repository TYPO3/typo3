..  include:: /Includes.rst.txt

..  _deprecation-111236-1794990000:

=========================================
Deprecation: #111236 - Module access gates
=========================================

See :issue:`111236`

Description
===========

The access to backend modules is decided by security voters, see
:ref:`feature-111236-1794934515`. The module access gates introduced with
TYPO3 v14.2 are therefore deprecated and will be removed in TYPO3 v16.0:

*   :php:`\TYPO3\CMS\Core\Attribute\AsModuleAccessGate`
*   :php:`\TYPO3\CMS\Backend\Module\ModuleAccessGateInterface`
*   :php:`\TYPO3\CMS\Backend\Module\ModuleAccessGateRegistry`
*   :php:`\TYPO3\CMS\Backend\Module\ModuleAccessResult`
*   :php:`\TYPO3\CMS\Backend\Module\AccessGate\AdminGate`,
    :php:`\TYPO3\CMS\Backend\Module\AccessGate\UserGate` and
    :php:`\TYPO3\CMS\Backend\Module\AccessGate\SystemMaintainerGate`

The access values `user`, `admin` and `systemMaintainer` are decided by the
:php:`\TYPO3\CMS\Backend\Security\Authorization\ModuleVoter`. The gates of
TYPO3 Core for these values are no longer used by TYPO3 Core.

Impact
======

A module access gate of an extension keeps working until TYPO3 v16.0: it is
asked through a voter for its access value, also when it replaces the gate
of TYPO3 Core for `user`, `admin` or `systemMaintainer`. Asking such a gate
triggers a PHP :php:`E_USER_DEPRECATED` error.

Affected installations
======================

Installations with extensions registering a module access gate with the
:php:`#[AsModuleAccessGate]` attribute, or using the classes listed above.
The extension scanner reports their usage.

Migration
=========

Decide the module access with a security voter. A voter can decide on an
access value of its own:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Security/ProjectLeadModuleVoter.php

    namespace MyVendor\MyExtension\Security;

    use Symfony\Component\Security\Core\Authorization\Voter\Vote;
    use TYPO3\CMS\Backend\Module\ModuleInterface;
    use TYPO3\CMS\Core\Attribute\AsSecurityVoter;
    use TYPO3\CMS\Core\Authentication\Principal;
    use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
    use TYPO3\CMS\Core\Security\Authorization\SecurityRole;
    use TYPO3\CMS\Core\Security\Authorization\Voter\PrincipalVoter;

    #[AsSecurityVoter]
    final class ProjectLeadModuleVoter extends PrincipalVoter
    {
        protected function supports(string $attribute, mixed $subject): bool
        {
            return $attribute === Scope::ACCESS
                && $subject instanceof ModuleInterface
                && $subject->getAccess() === 'projectLead';
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

A gate replacing the gate of TYPO3 Core for `user`, `admin` or
`systemMaintainer` only to restrict the access is migrated to a voter that
supports modules with that access value and denies: the denial of a voter
always wins over the grant of the :php:`ModuleVoter`. A voter cannot grant
more than the :php:`ModuleVoter` for these values.

..  index:: Backend, PHP-API, FullyScanned, ext:backend
