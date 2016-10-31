.. include:: ../../Includes.txt

==========================================================
Breaking: #78855 - Remove obsolete sys_action translations
==========================================================

See :issue:`78855`

Description
===========

These translations are removed from `EXT:sys_action/Resources/Private/Language/locallang.xlf`:

* action_BEu_hidden
* action_BEu_username
* action_BEu_password
* action_BEu_realName
* action_BEu_email
* action_BEu_usergroups

These translations are removed from `EXT:sys_action/Resources/Private/Language/locallang_tca.xlf`:

* tx_sys_action

Impact
======

Integrations / third party Extensions using these translations will break / output empty strings.


Affected Installations
======================

Installations that use the removed translations in third party code.


Migration
=========

Create your own `locallang.xlf` file and add the required translations.

.. index:: Backend
