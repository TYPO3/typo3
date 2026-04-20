..  include:: /Includes.rst.txt

..  _feature-109365-1774538611:

====================================================================
Feature: #109365 - Introduce module access gates for backend modules
====================================================================

See :issue:`109365`

Description
===========

The previously hard-coded module access checks (`user`, `admin`,
`systemMaintainer`) in the backend module registration have been replaced
with an extensible gate system. Each access type is now handled by a dedicated
gate class which implements
:php-short:`TYPO3\CMS\Backend\Module\ModuleAccessGateInterface`.

TYPO3 ships three built-in gates that preserve existing behavior:

*   :php-short:`\TYPO3\CMS\Backend\Module\AccessGate\UserGate` - grants access
    to admin users and users/groups with explicit module permissions
    (:sql:`be_users.userMods` / :sql:`be_groups.groupMods`)
*   :php-short:`\TYPO3\CMS\Backend\Module\AccessGate\AdminGate` - grants access
    only to admin users
*   :php-short:`\TYPO3\CMS\Backend\Module\AccessGate\SystemMaintainerGate` -
    grants access only to system maintainers

Extension authors can register custom gates using the
:php:`#[AsModuleAccessGate]` PHP attribute. A gate receives the module and the
current backend user and returns one of three results:

*   :php:`ModuleAccessResult::Granted` - access is explicitly allowed
*   :php:`ModuleAccessResult::Denied` - access is explicitly denied
*   :php:`ModuleAccessResult::Abstain` - the gate cannot decide (not responsible
    for this access type)

Example: Custom module access gate
----------------------------------

..  code-block:: php
    :caption: EXT:my_extension/Classes/Module/AccessGate/EditorGate.php

    namespace MyVendor\MyExtension\Module\AccessGate;

    use TYPO3\CMS\Backend\Module\ModuleAccessGateInterface;
    use TYPO3\CMS\Backend\Module\ModuleAccessResult;
    use TYPO3\CMS\Backend\Module\ModuleInterface;
    use TYPO3\CMS\Core\Attribute\AsModuleAccessGate;
    use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

    #[AsModuleAccessGate(identifier: 'editor')]
    final readonly class EditorGate implements ModuleAccessGateInterface
    {
        public function decide(
            ModuleInterface $module,
            BackendUserAuthentication $user,
        ): ModuleAccessResult {
            if ($module->getAccess() !== 'editor') {
                return ModuleAccessResult::Abstain;
            }
            // Custom logic: Check for a specific user group.
            return $user->check('groupList', '3')
                ? ModuleAccessResult::Granted
                : ModuleAccessResult::Denied;
        }
    }

The custom gate can then be referenced in the module registration:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Backend/Modules.php

    return [
        'my_module' => [
            'access' => 'editor',
            'labels' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang_mod.xlf',
            // ...
        ],
    ];

Ordering gates
--------------

Gates support `before` and `after` parameters to set their
evaluation order when multiple gates are registered:

..  code-block:: php

    #[AsModuleAccessGate(identifier: 'editor', after: ['user'])]
    final readonly class EditorGate implements ModuleAccessGateInterface
    {
        // ...
    }

Impact
======

Extension authors can now define custom module access strategies beyond the
built-in `user`, `admin`, and `systemMaintainer` levels by implementing
:php-short:`TYPO3\CMS\Backend\Module\ModuleAccessGateInterface` and
registering it with the :php:`#[AsModuleAccessGate]` attribute.

Existing module registrations using the built-in access values continue to
work without changes.

..  index:: Backend, PHP-API, ext:backend
