
.. include:: ../../Includes.txt

=============================================
Deprecation: #64922 - Deprecated entry points
=============================================

See :issue:`64922`

Description
===========

The following entry points have been marked as deprecated:

* typo3/tce_file.php
* typo3/move_el.php
* typo3/tce_db.php
* typo3/login_frameset.php
* typo3/sysext/cms/layout/db_new_content_el.php
* typo3/sysext/cms/layout/db_layout.php


Impact
======

Using one of the entry points in a backend module will throw a deprecation message.


Migration
=========

Use `\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl()` instead with the according module name.

typo3/tce_file.php
`\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('tce_file')`

typo3/move_el.php
`\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('move_element')`

typo3/tce_db.php
`\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('tce_db')`

typo3/login_frameset.php
`\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('login_frameset')`


.. index:: PHP-API, Backend
