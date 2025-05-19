:navigation-title: Update content

.. include:: /Includes.rst.txt
.. _content-update:

===========================================================
Synchronizing content and page structures across instances
===========================================================

The import/export tool can be used to synchronize content and page structures
between different TYPO3 installations, leaving the content
outside the exported page tree unchanged.

.. _content-update-backend:

Updating content using the TYPO3 backend
========================================

To update existing content without creating duplicates, check the option:

:guilabel:`Import > Import Options > Update records`

This ensures that records with matching UIDs are updated in place.

.. include:: /Images/AutomaticScreenshots/UpdateContent.rst.txt
