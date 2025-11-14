..  include:: /Includes.rst.txt

..  _feature-110269-1784737754:

====================================================================
Feature: #110269 - Allow row updater registration with PHP attribute
====================================================================

See :issue:`110269`

Description
===========

The new PHP attribute :php:`\TYPO3\CMS\Core\Attribute\AsRowUpdater` has been
added, which registers a class implementing
:php:`\TYPO3\CMS\Core\Upgrades\RowUpdater\RowUpdaterInterface` as a row updater
for the :php:`databaseRowsUpdateWizard` upgrade wizard.

Row updaters are executed by the "Execute database row updates" upgrade wizard.
It iterates over all rows of all TCA tables once and hands each row to every
registered row updater that announced a potential update for the table, which
makes them the tool of choice for data migrations that cannot be expressed as a
plain SQL statement.

Until now, the list of row updaters was a hard-coded, TYPO3 Core internal
property of :php:`\TYPO3\CMS\Core\Upgrades\DatabaseRowsUpdateWizard`. Extension
and project developers had no supported way to add their own implementation.
With the new attribute, registration is a matter of tagging the class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Upgrades/RowUpdater/MyRowUpdater.php

    namespace MyVendor\MyExtension\Upgrades\RowUpdater;

    use TYPO3\CMS\Core\Attribute\AsRowUpdater;
    use TYPO3\CMS\Core\Upgrades\RowUpdater\RowUpdaterInterface;

    #[AsRowUpdater('myExtensionMyRowUpdater')]
    final class MyRowUpdater implements RowUpdaterInterface
    {
        public function getTitle(): string
        {
            return 'Migrate my_field of tx_myextension_domain_model_entity records';
        }

        public function hasPotentialUpdateForTable(string $tableName): bool
        {
            return $tableName === 'tx_myextension_domain_model_entity';
        }

        public function updateTableRow(string $tableName, array $row): array
        {
            $row['my_field'] = $this->migrateValue($row['my_field']);
            return $row;
        }
    }

The identifier is optional. If it is omitted, the fully qualified class name is
used as identifier instead:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Upgrades/RowUpdater/MyRowUpdater.php

    #[AsRowUpdater]
    final class MyRowUpdater implements RowUpdaterInterface

Registration can alternatively be done manually with the service tag
:yaml:`install.rowupdater`, for example when the row updater class cannot carry
the attribute:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    services:
      MyVendor\MyExtension\Upgrades\RowUpdater\MyRowUpdater:
        tags:
          - name: 'install.rowupdater'
            identifier: 'myExtensionMyRowUpdater'

The identifier must be unique across all registered row updaters. It is stored
in the system registry once the row updater has been executed and is used to
determine which row updaters still need to run, so it should not be changed
afterwards.

Impact
======

Extensions and projects can now ship their own row updaters and have them
executed by the "Execute database row updates" upgrade wizard, both in the
:guilabel:`Admin Tools > Upgrade` backend module and with the
:bash:`upgrade:run` console command.

Registered row updaters are listed with their title, can be marked as done or
undone individually, and are picked up automatically - no further configuration
is needed beyond the attribute or the service tag.

..  index:: CLI, PHP-API, ext:core
