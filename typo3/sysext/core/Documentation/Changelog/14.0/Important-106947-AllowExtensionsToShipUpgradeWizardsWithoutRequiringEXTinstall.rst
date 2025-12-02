..  include:: /Includes.rst.txt

..  _important-106947-1750759104:

============================================================================================
Important: #106947 - Allow extensions to ship upgrade wizards without requiring `EXT:install`
============================================================================================

See :issue:`106947`

Description
===========

`EXT:install` is no longer installed per default in composer based
minimal installations.

However, this advantage could often not be utilised sensibly, since
extensions still needed to require `EXT:install` as a dependency in order to
ship upgrade wizards, because the implemented interfaces needed to be available.

The basic required interfaces and the PHP attribute are now moved to the `EXT:core`
namespace. The old counterparts are deprecated with
:ref:`deprecation-106947-1750759241` but are still provided by `EXT:install` for
the time being.

The following changes are required (when requiring TYPO3 14.0+) to make `EXT:install`
optional:

*   Let custom upgrade wizards implement
    :php:`\TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface`.
*   Implement custom list-type to CType migrations extending
    :php:`\TYPO3\CMS\Core\Upgrades\AbstractListTypeToCTypeUpdate`.
*   Use the attribute :php:`\TYPO3\CMS\Core\Attribute\UpgradeWizard` to
    register custom upgrade wizards.

..  note::

    The wording has been streamlined to use `Upgrade` instead of `Update` to
    better align with their intention and with the naming in the TYPO3 command
    line interface.

Extension authors supporting two major TYPO3 versions with one extension version can
follow these strategies:

*   **TYPO3 v13 and v14**: Use deprecated `EXT:install` interfaces and
    PHP Attribute and require `EXT:install` as mandatory dependency, or
    add it as a suggestion to allow the decision to be made on project-level.
*   **TYPO3 v14**: Switch to `EXT:core` interfaces and the new PHP Attribute.

..  note::

    The related upgrade wizard CLI command and services have been moved to
    `EXT:core`, allowing to execute TYPO3 Core and extension upgrade wizards on
    deployments via CLI commands and making `EXT:install` optional for them.

See :ref:`deprecation-106947-1750759241` for details about moved interfaces,
PHP attribute and a migration example.


..  index:: PHP-API, ext:core
