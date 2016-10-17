.. include:: ../../Includes.txt

======================================
Breaking: #77630 - Remove wizard icons
======================================

See :issue:`77630`

Description
===========

The following icons have been removed from `typo3/sysext/backend/Resources/Public/Images/FormFieldWizard/`:

- wizard_add.gif
- wizard_edit.gif
- wizard_list.gif
- wizard_table.gif
- wizard_link.gif
- wizard_rte.gif


Impact
======

The mentioned icons can not be used anymore.


Affected Installations
======================

Any installation using those icons.


Migration
=========

The TCA migration migrates the icon calls to the new output if used as wizard icon.

- `wizard_add.gif` => `actions-add`
- `wizard_edit.gif` => `actions-open`
- `wizard_list.gif` => `actions-system-list-open`
- `wizard_table.gif` => `content-table`
- `wizard_link.gif` => `actions-wizard-link`
- `wizard_rte.gif` => `actions-wizard-rte`

.. index:: Backend
