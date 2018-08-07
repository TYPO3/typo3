.. include:: ../../Includes.txt

==========================================================
Breaking: #78855 - Remove obsolete sys_action translations
==========================================================

See :issue:`78855`

Description
===========

These translations have been removed from :file:`EXT:sys_action/Resources/Private/Language/locallang.xlf`:

* action_BEu_hidden
* action_BEu_username
* action_BEu_password
* action_BEu_realName
* action_BEu_email
* action_BEu_usergroups

These translations have been removed from :file:`EXT:sys_action/Resources/Private/Language/locallang_tca.xlf`:

* tx_sys_action


Impact
======

Integrations / third party Extensions using these translation-keys within the mentioned files will output empty strings.


Affected Installations
======================

Installations that use the removed translations in third party code.


Migration
=========

Create your own :file:`locallang.xlf` file and add the required translations.

.. index:: Backend, ext:sys_action
