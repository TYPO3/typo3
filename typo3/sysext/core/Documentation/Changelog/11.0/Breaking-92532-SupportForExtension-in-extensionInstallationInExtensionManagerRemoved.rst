.. include:: /Includes.rst.txt

===============================================================================================
Breaking: #92532 - Support for extension-in-extension installation in Extension Manager removed
===============================================================================================

See :issue:`92532`

Description
===========

The installation process within the Extension Manager allowed extensions to be
installed having custom dependencies to other extensions in
:file:`EXT:my_extension/Initialisation/Extensions/third_party_ext`.

This feature was originally introduced for the Introduction Package,
which had a few more dependencies until TYPO3 v9.

As this (undocumented) feature was not used in public for any other extensions,
and since Extension Manager can fetch dependencies from TER directly as well,
this feature is removed.


Impact
======

If an extension is installed which contains other extensions as
dependencies in :file:`Initialisation/Extensions/*` they are now ignored
on installation, and instead looked up in the remote TYPO3 Extension Repository,
as with any other depending extension.


Affected Installations
======================

TYPO3 extensions using this dependency management as "Extension-in-Extension"
functionality.


Migration
=========

Upload the proper extension into https://extensions.typo3.org and remove
the folder :file:`Initialisation/Extensions` from any custom extensions.

.. index:: PHP-API, FullyScanned, ext:extensionmanager
