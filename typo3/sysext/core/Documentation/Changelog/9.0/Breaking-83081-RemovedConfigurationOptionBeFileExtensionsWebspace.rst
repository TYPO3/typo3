.. include:: /Includes.rst.txt

==========================================================================
Breaking: #83081 - Removed configuration option BE/fileExtensions/webspace
==========================================================================

See :issue:`83081`

Description
===========

The file extensions which are allowed to be uploaded, which were previously available under
:php:`$TYPO3_CONF_VARS[BE][fileExtensions][webspace]` called `allow` and `deny` have been removed.


Impact
======

* Using the old configuration option names will result in a PHP notice.
* In Import/Export when uploading files :php:`fileDenyPattern` is used instead of `allow` and `deny`
* When using :php:`BasicFileUtility` directly, only :php:`fileDenyPattern` is used


Affected Installations
======================

TYPO3 installations which have set this option in :file:`LocalConfiguration.php` previously, or extensions which
still use the old configuration option names.


Migration
=========

Use :php:`fileDenyPattern` which is used consistently throughout the core to deny specific file extensions.

.. index:: LocalConfiguration, PartiallyScanned
