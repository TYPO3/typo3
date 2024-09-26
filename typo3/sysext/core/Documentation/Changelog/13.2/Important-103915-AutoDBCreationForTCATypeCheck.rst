.. include:: /Includes.rst.txt

.. _important-103915-1716666919:

=========================================================================
Important: #103915 - Adjust database field defaults for "check" TCA types
=========================================================================

See :issue:`103915`

Description
===========

TYPO3 v13.0 has introduced automatic database field creation for TCA
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

All these records, created via :php:`DataHandler` calls, actually
evaluate the TCA default for record insertion and do not rely
on SQL database field defaults.

Only records created using the :php:`QueryBuilder` or
other "raw" database calls would apply the wrong
values.

An example of this is
:php:`TYPO3\CMS\Core\Resource\StorageRepository->createLocalStorage()`
which creates a default :file:`fileadmin` record via the :php:`QueryBuilder`
and then sets the field :sql:`auto_extract_metadata` to :sql:`0`, instead of `1`
as would be expected in the TCA. This would mean YouTube files would not
automatically fetch metadata on creation.

This means, for all custom extension code that

* removed the column definition in :file:`ext_tables.sql`
  to enforce automatic database field creation,
* *and* did not use the recommended :php:`DataHandler` for
  record insertion (so, any code that is not executed in the
  backend context, using :php:`QueryBuilder` or Extbase
  repository methods),
* *and* expects a different default than :sql:`0` for newly
  created records,
* *and* relied on the database field definition default

this code may have created incorrect database records for versions between
TYPO3 v13.0 and 13.2.

For TYPO3 Core code, this has only affected:

* Default file storage creation, field :sql:`sys_file_metadata.auto_extract_metadata`
* Default backend user creation (admin) property :sql:`be_users.options`

In these rare case, the database record integrity needs to be
checked manually, because there are no automated tools
to see if a record has used SQL default values or specifically
defined values.

.. index:: Database, ext:core
