=================================================================
Breaking: #61859 - deprecated file type FILETYPE_SOFTWARE removed
=================================================================

Description
===========

The constant :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_SOFTWARE` has been removed.


Impact
======

Using the removed constant will result in a fatal error.


Affected installations
======================

Any installation using an extension that uses the constant :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_SOFTWARE`.


Migration
=========

Use :php:`\TYPO3\CMS\Core\Resource\AbstractFile::FILETYPE_APPLICATION` instead, which matches the Iana standard.
