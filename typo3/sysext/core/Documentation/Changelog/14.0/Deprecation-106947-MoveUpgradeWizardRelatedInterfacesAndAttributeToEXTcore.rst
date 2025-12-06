..  include:: /Includes.rst.txt

..  _deprecation-106947-1750759241:

=========================================================================================
Deprecation: #106947 - Move upgrade wizard related interfaces and attribute to `EXT:core`
=========================================================================================

See :issue:`106947`

Description
===========

`EXT:install` provided a couple of interfaces to allow implementing upgrade
wizards, along with the PHP attribute :php:`#[UpgradeWizard(...)]` to
register them.

Since TYPO3 v13 it is possible to run TYPO3 without `EXT:install` being
installed. However, this advantage could often not be utilised sensibly,
since extensions still needed to require `EXT:install` as a dependency in
order to ship upgrade wizards, because the implemented interfaces needed to
be available.

For this reason the following interfaces, classes and attributes are now moved
into `EXT:core`, and their former counterparts in `EXT:install` now extend
these Core classes:

*   Attribute :php:`\TYPO3\CMS\Install\Attribute\UpgradeWizard` to
    :php:`\TYPO3\CMS\Core\Attribute\UpgradeWizard`
*   Interface :php:`\TYPO3\CMS\Install\Updates\ChattyInterface` to
    :php:`\TYPO3\CMS\Core\Upgrades\ChattyInterface`
*   Interface :php:`\TYPO3\CMS\Install\Updates\ConfirmableInterface` to
    :php:`\TYPO3\CMS\Core\Upgrades\ConfirmableInterface`
*   Interface :php:`\TYPO3\CMS\Install\Updates\PrerequisiteInterface` to
    :php:`\TYPO3\CMS\Core\Upgrades\PrerequisiteInterface`
*   Interface :php:`\TYPO3\CMS\Install\Updates\RepeatableInterface` to
    :php:`\TYPO3\CMS\Core\Upgrades\RepeatableInterface`
*   Interface :php:`\TYPO3\CMS\Install\Updates\UpgradeWizardInterface` to
    :php:`\TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface`
*   Class :php:`\TYPO3\CMS\Install\Updates\Confirmation` to
    :php:`\TYPO3\CMS\Core\Upgrades\Confirmation`
*   Class :php:`\TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite`
    to :php:`\TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite`
*   Class :php:`\TYPO3\CMS\Install\Updates\ReferenceIndexUpdatedPrerequisite`
    to :php:`\TYPO3\CMS\Core\Upgrades\ReferenceIndexUpdatedPrerequisite`
*   AbstractClass :php:`\TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate`
    to :php:`\TYPO3\CMS\Core\Upgrades\AbstractListTypeToCTypeUpdate`

The following internal class has been moved without an alternative:

*   Class :php`\TYPO3\CMS\Install\Updates\PrerequisiteCollection`
    to :php:`\TYPO3\CMS\Core\Upgrades\PrerequisiteCollection`

Extension authors are encouraged to migrate their upgrade wizards to the new
core interface, in order to drop their dependency on `EXT:install`.

..  note::

    `EXT:install` is still required to execute upgrade wizards either on CLI
    or within the TYPO3 Install Tool interface, even when switching to the
    new `EXT:core` interfaces. Moving `typo3 upgrade:*` commands is planned
    for TYPO3 v14 LTS.

..  note::

    Note that not only "Install" is replaced with "Core", but also "Updates"
    renamed to "Upgrades" to keep a consistent naming scheme.

Impact
======

Using the listed interfaces and the attribute in the scope of `EXT:core` allow extension
authors to optionally provide their extension upgrade wizards.
`EXT:install` can then be set as a "suggested" dependency, and is no longer
required to be mandatory.

The usage of these old interfaces from `EXT:install` is now deprecated.

Affected installations
======================

Installations missing `EXT:install` or having extensions providing upgrade wizards
using the old namespace.

Migration
=========

Upgrade custom upgrade wizards to use the new attribute, interfaces
and/or classes from the `EXT:core` namespace, instead of `EXT:install`:

Upgrade Wizard
--------------

Before
^^^^^^

..  code-block:: php
    :caption: EXT:my_extension/Classes/Upgrades/CustomUpgradeWizard.php
    :emphasize-lines: 7-11

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Upgrades;

    use TYPO3\CMS\Install\Attribute\UpgradeWizard;
    use TYPO3\CMS\Install\Updates\ChattyInterface;
    use TYPO3\CMS\Install\Updates\ConfirmableInterface;
    use TYPO3\CMS\Install\Updates\RepeatableInterface;
    use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

    #[UpgradeWizard('myExtensionCustomUpgradeWizardIdentifier')]
    class CustomUpgradeWizard implements UpgradeWizardInterface, ChattyInterface, RepeatableInterface
    {
        // ...
    }

After
^^^^^

..  code-block:: php
    :caption: EXT:my_extension/Classes/Upgrades/CustomUpgradeWizard.php
    :emphasize-lines: 7-11

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Upgrades;

    use TYPO3\CMS\Core\Attribute\UpgradeWizard;
    use TYPO3\CMS\Core\Upgrades\ChattyInterface;
    use TYPO3\CMS\Core\Upgrades\ConfirmableInterface;
    use TYPO3\CMS\Core\Upgrades\RepeatableInterface;
    use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;

    #[UpgradeWizard('myExtensionCustomUpgradeWizardIdentifier')]
    class CustomUpgradeWizard implements UpgradeWizardInterface, ChattyInterface, RepeatableInterface
    {
        // ...
    }

AbstractListTypeToCTypeUpdate
-----------------------------

The abstract upgrade wizard :php:`\TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate`
has been introduced in 13.4 and is quite young, but needed to be moved to `EXT:core` to
provide implementing upgrade wizards utilizing this base class.

..  note::

    Any extension that utilizes this abstract needs to require at least TYPO3 v14.0
    when using the new base class.

Before
^^^^^^

..  code-block:: php
    :caption: EXT:my_extension/Classes/Upgrades/CustomCTypeMigration.php
    :emphasize-lines: 8

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Upgrades;

    use TYPO3\CMS\Core\Attribute\UpgradeWizard;
    use TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate;

    #[UpgradeWizard('CustomCTypeMigration')]
    final class CustomCTypeMigration extends AbstractListTypeToCTypeUpdate
    {
        // ...
    }

After
^^^^^

..  code-block:: php
    :caption: EXT:my_extension/Classes/Upgrades/CustomCTypeMigration.php
    :emphasize-lines: 8

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Upgrades;

    use TYPO3\CMS\Core\Attribute\UpgradeWizard;
    use TYPO3\CMS\Core\Upgrades\AbstractListTypeToCTypeUpdate;

    #[UpgradeWizard('CustomCTypeMigration')]
    final class CustomCTypeMigration extends AbstractListTypeToCTypeUpdate
    {
        // ...
    }


EXT:install prerequisite
------------------------

Using the prerequisite classes from the `EXT:install` namespace
(using their compatibility alias) can be kept for the time being,
if an extension needs to provide support for two major TYPO3 versions:

..  code-block:: php
    :caption: TYPO3 v13 and v14 dual version support.
    :emphasize-lines: 12-13,24-25

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Upgrades;

    use TYPO3\CMS\Core\Attribute\UpgradeWizard;
    use TYPO3\CMS\Core\Upgrades\ChattyInterface;
    use TYPO3\CMS\Core\Upgrades\ConfirmableInterface;
    use TYPO3\CMS\Core\Upgrades\RepeatableInterface;
    use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;
    use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
    use TYPO3\CMS\Install\Updates\ReferenceIndexUpdatedPrerequisite;

    #[UpgradeWizard('myExtensionCustomUpgradeWizardIdentifier')]
    class CustomUpgradeWizard implements UpgradeWizardInterface, ChattyInterface, RepeatableInterface
    {
        /**
         * @return string[] All new fields and tables must exist
         */
        public function getPrerequisites(): array
        {
            return [
                DatabaseUpdatedPrerequisite::class,
                ReferenceIndexUpdatedPrerequisite::class,
            ];
        }
    }

..  code-block:: php
    :caption: TYPO3 v14 and newer use core classes
    :emphasize-lines: 12-13,24-25

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Upgrades;

    use TYPO3\CMS\Core\Attribute\UpgradeWizard;
    use TYPO3\CMS\Core\Upgrades\ChattyInterface;
    use TYPO3\CMS\Core\Upgrades\ConfirmableInterface;
    use TYPO3\CMS\Core\Upgrades\RepeatableInterface;
    use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;
    use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
    use TYPO3\CMS\Core\Upgrades\ReferenceIndexUpdatedPrerequisite;

    #[UpgradeWizard('myExtensionCustomUpgradeWizardIdentifier')]
    class CustomUpgradeWizard implements UpgradeWizardInterface, ChattyInterface, RepeatableInterface
    {
        /**
         * @return string[] All new fields and tables must exist
         */
        public function getPrerequisites(): array
        {
            return [
                DatabaseUpdatedPrerequisite::class,
                ReferenceIndexUpdatedPrerequisite::class,
            ];
        }
    }

..  index:: PHP-API, FullyScanned, ext:install
