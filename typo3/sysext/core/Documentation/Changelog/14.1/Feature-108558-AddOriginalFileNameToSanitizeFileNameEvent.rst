..  include:: /Includes.rst.txt

..  _feature-108558-1766399298:

==================================================================
Feature: #108558 - Add original file name to SanitizeFileNameEvent
==================================================================

See :issue:`108558`

Description
===========

The PSR-14 event :php:`\TYPO3\CMS\Core\Resource\Event\SanitizeFileNameEvent`
does now also provide the original file name. Event listeners can use the
original file name to perform custom string replacements (e.g. space to
hyphen instead of underscore) for the sanitized file name.


Impact
======

It is now possible to retrieve the original file name in the PSR-14 event
:php:`\TYPO3\CMS\Core\Resource\Event\SanitizeFileNameEvent`.

..  index:: PHP-API, ext:core
