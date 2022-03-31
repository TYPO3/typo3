.. include:: /Includes.rst.txt

======================================
Breaking: #77630 - Remove wizard icons
======================================

See :issue:`77630`

Description
===========

The following icons have been removed from :file:`typo3/sysext/backend/Resources/Public/Images/FormFieldWizard/`:

- :file:`wizard_add.gif`
- :file:`wizard_edit.gif`
- :file:`wizard_list.gif`
- :file:`wizard_table.gif`
- :file:`wizard_link.gif`
- :file:`wizard_rte.gif`


Impact
======

The mentioned icons can not be used anymore.


Affected Installations
======================

Any installation using those icons.


Migration
=========

The TCA migration migrates the icon calls to the new output if used as wizard icon.

- :file:`wizard_add.gif` => `actions-add`
- :file:`wizard_edit.gif` => `actions-open`
- :file:`wizard_list.gif` => `actions-system-list-open`
- :file:`wizard_table.gif` => `content-table`
- :file:`wizard_link.gif` => `actions-wizard-link`
- :file:`wizard_rte.gif` => `actions-wizard-rte`

.. index:: Backend
