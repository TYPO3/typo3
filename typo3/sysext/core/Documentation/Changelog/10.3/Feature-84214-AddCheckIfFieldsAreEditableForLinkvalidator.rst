.. include:: /Includes.rst.txt

====================================================================
Feature: #84214 - Add check if fields are editable for Linkvalidator
====================================================================

See :issue:`84214`

Description
===========

Broken links should only be shown in the list of broken links,
if current backend user has edit access to the field. This way
the editor will no longer get an error message on trying to
edit records he has no permission to edit.

Whether the editor has access depends on a number of factors.

We check the following:

* The current permissions of the page. For editing the page, the editor must have
  Permission::PAGE_EDIT, for editing content Permission::CONTENT_EDIT must be available.
* The user has write access to the table. We check if the table
  is in 'tables_modify' for the group(s).
* The user has write access to the field. We check if the field
  is an exclude field. If yes, it must be included in
  'non_exclude_fields' for the group(s).
* The user has write permission for the language of the record.
* For tt_content: The CType is in list of explicitly allowed
  values for authMode.

Impact
======

* Broken links for fields that are not editable for the current backend
  user will no longer be shown.
* Fields were added to the :sql:`tx_linkvalidator_link` table. "Analyze
  Database Structure" must be executed.
* After an update to the new version, checking of broken links should
  be reinitialized for the entire site. Until this is done, some broken
  links may not be displayed for editors in the broken link report.

.. index:: Backend, ext:linkvalidator
