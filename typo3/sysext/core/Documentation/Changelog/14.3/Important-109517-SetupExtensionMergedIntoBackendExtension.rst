..  include:: /Includes.rst.txt

..  _important-109517-1744105200:

==================================================================
Important: #109517 - Setup extension merged into backend extension
==================================================================

See :issue:`109517`

Description
===========

The system extension `setup` (`typo3/cms-setup`) existed for historical reasons
as a separate package. It provided the "User Settings" backend module, where users
could change their password, name, email, language, avatar and other personal
preferences.

In modern web applications a user profile module should never be optional, so
the extension has been fully merged into the `backend` extension
(`typo3/cms-backend`). The module is now always available when the backend is
installed and can still be hidden for individual users via user TSconfig. The
separate package is no longer needed and should not be referenced in new
installations.

For Composer-based installations
--------------------------------

The Composer package `typo3/cms-backend` now **replaces** `typo3/cms-setup`.
This means:

*   There is no need to require `typo3/cms-setup` in :file:`composer.json`
    anymore. Existing references are resolved automatically because
    `typo3/cms-backend` declares that it replaces the package.
*   No manual action is required during an upgrade – Composer handles the
    replacement transparently.
*   New projects should **not** add `typo3/cms-setup` as a dependency.

For class-based references
--------------------------

The following public classes have been moved and class aliases are in place for
backwards compatibility:

*   :php:`TYPO3\CMS\Setup\Event\AddJavaScriptModulesEvent`
    → :php:`TYPO3\CMS\Backend\Event\AddUserSettingsJavaScriptModulesEvent`
*   :php:`TYPO3\CMS\Setup\Form\Element\AvatarElement`
    → :php:`TYPO3\CMS\Backend\Form\Element\AvatarElement`
*   :php:`TYPO3\CMS\Setup\UserFunctions\UserSettingsItemsProcFunc`
    → :php:`TYPO3\CMS\Backend\UserFunctions\UserSettingsItemsProcFunc`

Extensions using the old class names will continue to work, but should be
updated to the new namespaces.

.. _important-109517-1744105200-AddJavaScriptModulesEvent:

Moved event `AddJavaScriptModulesEvent`
---------------------------------------

A special case is :php:`TYPO3\CMS\Setup\Event\AddJavaScriptModulesEvent`. This file
has been moved to :file:`typo3/sysext/backend/DeprecatedClasses/Setup/Event/AddJavaScriptModulesEvent.php`
and is added as a specific PSR-4 autoload entry to the Core's :file:`composer.json`
map, so that the legacy event can be dispatched properly. No deprecation
message is emitted when dispatching this legacy event.

In addition, a new event :php:`TYPO3\CMS\Backend\Event\AddUserSettingsJavaScriptModulesEvent`
has been added with a distinguishing name. Both events are dispatched in TYPO3 v14,
with the legacy event being deprecated and removed in TYPO3 v15
(see :ref:`deprecation-109517-1744105201`).

Extensions providing compatibility to two versions should proceed as below:

.. _important-109517-1744105200-AddJavaScriptModulesEvent-v13:

For compatibility with TYPO3 v13 and v14
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Only listen to the legacy event :php:`TYPO3\CMS\Setup\Event\AddJavaScriptModulesEvent`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Listener/SetupModuleListener.php
    :emphasize-lines: 7,12

    <?php
    declare(strict_types=1);

    namespace MyExtension\Listener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Setup\Event\AddJavaScriptModulesEvent;

    final class SetupModuleListener
    {
        #[AsEventListener('my-extension/setup-module-listener')]
        public function __invoke(AddJavaScriptModulesEvent $event): void
        {
            $event->addJavaScriptModule('@my-extension/setupModule/some-file.js');
        }
    }

.. _important-109517-1744105200-AddJavaScriptModulesEvent-v14:

For compatibility with TYPO3 v14 and v15
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Only listen to the new event :php:`TYPO3\CMS\Backend\Event\AddUserSettingsJavaScriptModulesEvent`:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Listener/SetupModuleListener.php
    :emphasize-lines: 6,12

    <?php
    declare(strict_types=1);

    namespace MyExtension\Listener;

    use TYPO3\CMS\Backend\Event\AddUserSettingsJavaScriptModulesEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class SetupModuleListener
    {
        #[AsEventListener('my-extension/setup-module-listener')]
        public function __invoke(AddUserSettingsJavaScriptModulesEvent $event): void
        {
            $event->addJavaScriptModule('@my-extension/setupModule/some-file.js');
        }
    }

..  index:: Backend, PHP-API, ext:backend, NotScanned
