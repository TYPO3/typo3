
.. include:: /Includes.rst.txt

====================================================================
Feature: #68600 - Introduced ResourceStorage SanitizeFileName signal
====================================================================

See :issue:`68600`

Description
===========

In order to check whether an uploaded/newly added file already exists before uploading it or to ask for
user preferences about already existing files only when needed, the final name for the uploaded file is needed.

In order to let extensions do custom sanitizing of a file name the signal `sanitizeFileName` is introduced in
`TYPO3\CMS\Core\Resource\ResourceStorage`.
The signal is emitted when `ResourceStorage::sanitizeFileName` or `ResourceStorage::addFile` are called.


Impact
======

All installations with extensions that use the PreFileAdd signal to change/sanitize a file name.
This logic should be moved to the new sanitizeFileName signal.


.. index:: PHP-API, FAL
