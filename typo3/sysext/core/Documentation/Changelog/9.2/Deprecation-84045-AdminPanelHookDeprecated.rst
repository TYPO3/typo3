.. include:: ../../Includes.txt

================================================
Deprecation: #84045 - AdminPanel Hook deprecated
================================================

See :issue:`84045`

Description
===========

The hook `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel']` has been
marked as deprecated along with the corresponding interface `\TYPO3\CMS\Frontend\View\AdminPanelViewHookInterface`.


Impact
======

Using either the interface or registering the hook will result in a deprecation warning and will stop working in future
TYPO3 versions.


Affected Installations
======================

Installations using the `\TYPO3\CMS\Frontend\View\AdminPanelViewHookInterface`.


Migration
=========

Use the new admin panel module API starting with TYPO3 v9.2.

.. index:: Frontend, FullyScanned, ext:frontend
