.. include:: ../../Includes.txt

====================================================================================================
Important: #85393 - Extension Manager only imports extensions compatible with TYPO3 v7 LTS or higher
====================================================================================================

See :issue:`85393`

Description
===========

The extension manager now includes a restriction when updating the list of current extensions
within TER that have been uploaded later than November 10th, 2015 - the release of
TYPO3 v7 LTS (7.6.0).

This ensures that the database is drastically reduced, resulting in smaller footprint for the
database and faster updating / searching / browsing the list of available extensions within the
Extension Manager.

Installing extensions older than this date is still possible by manually downloading an extension
and importing it via a ZIP file or by uploading it into :file:`typo3conf/ext/[extension_name]`.

.. index:: ext:extensionmanager
