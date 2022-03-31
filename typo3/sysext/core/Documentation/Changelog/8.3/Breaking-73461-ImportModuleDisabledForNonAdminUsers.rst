
.. include:: /Includes.rst.txt

=============================================================
Breaking: #73461 - Import module disabled for non admin users
=============================================================

See :issue:`73461`

Description
===========

The import module of EXT:impexp has been disabled for non-admin users by default.


Impact
======

For non-admin users who need that functionality, the userTsConfig option :typoscript:`options.impexp.enableImportForNonAdminUser = 1`
can be set. This can become a security problem to the TYPO3 instance in core versions
7.6 and 6.2 and should only be enabled for "trustworthy" backend users in general.

Affected Installations
======================

Installations with non-admin users making active use of the import / export module


Migration
=========

Set userTsConfig option :typoscript:`options.impexp.enableImportForNonAdminUser = 1` to restore the old behavior.

.. index:: TSConfig, ext:impexp
