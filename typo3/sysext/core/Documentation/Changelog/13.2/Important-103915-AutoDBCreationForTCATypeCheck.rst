.. include:: /Includes.rst.txt

.. _important-103915-1716666919:

=========================================================================
Important: #103915 - Adjust database field defaults for "check" TCA types
=========================================================================

See :issue:`103915`

Description
===========

TYPO3 v13.0 introduced the automatic database field creation for TCA
fields configured as type "check" (if not explicitly defined in
:file:`ext_tables.sql`), via
`https://review.typo3.org/c/Packages/TYPO3.CMS/+/80513`__.

This conversion applied a :sql:`default 0` to all fields, and did not
evaluate the actual TCA definition for the
:php:`['config']['default']` setting.

This bug has been fixed, and the DB schema analyzer will now convert
all the following fields to their proper default settings:

* :sql:`be_users.options`                                 (0->3)
* :sql:`sys_file_storage.is_browsable`                    (0->1)
* :sql:`sys_file_storage.is_writable`                     (0->1)
* :sql:`sys_file_storage.is_online`                       (0->1)
* :sql:`sys_file_storage.auto_extract_metadata`           (0->1)
* :sql:`sys_file_metadata.visible`                        (0->1)
* :sql:`tt_content.sectionIndex`                          (0->1)
* :sql:`tx_styleguide_palette.palette_1_1`                (0->1)
* :sql:`tx_styleguide_palette.palette_1_3`                (0->1)
* :sql:`tx_styleguide_valuesdefault.checkbox_1`           (0->1)
* :sql:`tx_styleguide_valuesdefault.checkbox_2`           (0->1)
* :sql:`tx_styleguide_valuesdefault.checkbox_3`           (0->5)
* :sql:`tx_styleguide_valuesdefault.checkbox_4`           (0->5)
* :sql:`sys_workspace.edit_allow_notificaton_settings`    (0->3)
* :sql:`sys_workspace.edit_notification_preselection`     (0->2)
* :sql:`sys_workspace.publish_allow_notificaton_settings` (0->3)
* :sql:`sys_workspace.publish_notification_preselection`  (0->1)
* :sql:`sys_workspace.execute_allow_notificaton_settings` (0->3)
* :sql:`sys_workspace.execute_notification_preselection`  (0->3)
* :sql:`sys_workspace_stage.allow_notificaton_settings`   (0->3)
* :sql:`sys_workspace_stage.notification_preselection`    (0->8)

All these records created via :php:`DataHandler` calls actually
interpret the TCA default for record insertion and do not rely
on SQL database field defaults.

Only if records were created using the :php:`QueryBuilder` or
other "raw" database calls, this would cause applying wrong
values.

An example for this is
:php:`TYPO3\CMS\Core\Resource\StorageRepository->createLocalStorage()`
which creates the default :file:`fileadmin` storage record, and would do
that via the :php:`QueryBuilder` and then setting this storage with
the field :sql:`auto_extract_metadata` to :sql:`0` instead of the
TCA expectation of `1`. This would then cause inserted
YouTube files to not automatically fetch metadata on creation.

This means, for all custom extension code that

* removed the column definition in :file:`ext_tables.sql`
  to enforce automatic database field creation,
* *and* did not use the recommended :php:`DataHandler` for
  record insertion (so, any code that is not executed in
  backend context, using :php:`QueryBuilder` or Extbase
  repository methods),
* *and* expects a different default than :sql:`0` for newly
  created records,
* *and* relied on the database field definition default

may have created wrong database records when used between
TYPO3 v13.0 and v13.2.

For TYPO3 Core code, this has only affected:

* Default file storage creation, field :sql:`sys_file_metadata.auto_extract_metadata`
* Default backend user creation (admin) property :sql:`be_users.options`

In this rare case, the database record integrity needs to be
manually evaluated, because there is no automation available
to see if a record has used SQL default values or specifically
defined values.

.. index:: Database, ext:core
