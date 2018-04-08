.. include:: ../../Includes.txt

=========================================================================
Deprecation: #84463 - PageTsConfig option mod.web_list.newWizards dropped
=========================================================================

See :issue:`84463`

Description
===========

The widely unknown PageTsConfig option :ts:`mod.web_list.newWizards` has been enabled by default and dropped.

PHP property :php:`newWizards` of class :php:`TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList` has been deprecated
along the way.


Impact
======

The "+" sign in the list module of `pages` table now by default links to the wizard to select the new page position.

The "+" sign in the list module of `tt_content` table now by default links to the new content element wizard in a modal.


Affected Installations
======================

Most installations should not be affected by the code change, the extension scanner will find extensions using the
mentioned class property.


Migration
=========

Do not use property :php:`newWizards` anymore, drop the PageTsConfig option if used.

.. index:: Backend, PHP-API, TSConfig, PartiallyScanned