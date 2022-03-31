.. include:: /Includes.rst.txt

========================================================================================
Breaking: #88525 - Remove "createDirs" directive of extension installation / em_conf.php
========================================================================================

See :issue:`88525`

Description
===========

Every TYPO3 extension has a file called :file`ext_emconf.php` where important information regarding
dependencies, current version and loading order are stored.

The directive :php:`createDirs` that was responsible to create a list of folders in the file structure
during extension installation has been dropped.

The option was available before any File Abstraction Layer. As the :file:`uploads/` folder is not
created by default by TYPO3 anymore, this directive is not supported anymore as well, as TYPO3 strives
to support unified file handling for content files, volatile files (file uploads within :file:`typo3temp/var/`)
or within Extensions directly. The Environment API, introduced in TYPO3 v9, should support for PHP-based
APIs to choose / create a correct folder location.


Impact
======

Extensions having this directive set will not have this folder available at installation time
of the extension. The folder will not be created for newly installed extensions, existing extensions
when upgrading from previous TYPO3 versions, will continue to exist.


Affected Installations
======================

Any TYPO3 extension having this property within :file:`ext_emconf.php` set.


Migration
=========

When an extension supports TYPO3 v10+ only, this directive can be removed.

If an extension needs a special directory, this should be created via PHP when it is needed
via e.g. :php:`GeneralUtility::mkdir_deep()`.

.. index:: PHP-API, NotScanned, ext:extensionmanager
