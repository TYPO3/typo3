.. include:: /Includes.rst.txt

====================================================================================
Important: #79119 - Removed PageRepository->versioningPreview_where_hid_del property
====================================================================================

See :issue:`79119`

Description
===========

The public property `$versioningPreview_where_hid_del` inside the PHP class `PageRepository` was not used anymore
due to the refactoring of the database queries based on Doctrine DBAL and has been removed.

.. index:: Frontend, PHP-API
