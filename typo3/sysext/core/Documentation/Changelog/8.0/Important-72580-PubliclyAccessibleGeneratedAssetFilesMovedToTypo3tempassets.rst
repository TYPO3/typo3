
.. include:: /Includes.rst.txt

========================================================================================
Important: #72580 - Publicly accessible generated asset files moved to typo3temp/assets/
========================================================================================

See :issue:`72580`

Description
===========

The folder structure within typo3temp/ was changed to separate assets that need to be accessed by
the client from the files that are temporary created for e.g. caching or locking purposes and only need
server-side access.

These assets were moved from the folders `\_processed\_`, `compressor`, `GB`, `temp`, `Language`,
`pics` and organized into `typo3temp/assets/js/`, `typo3temp/assets/css/`,
`typo3temp/assets/compressed/` and `typo3temp/assets/images/`.

.. index:: Frontend
