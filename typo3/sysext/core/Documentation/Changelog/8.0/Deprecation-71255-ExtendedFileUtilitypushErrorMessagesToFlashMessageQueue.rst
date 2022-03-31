
.. include:: /Includes.rst.txt

=================================================================================
Deprecation: #71255 - ExtendedFileUtility::pushErrorMessagesToFlashMessageQueue()
=================================================================================

See :issue:`71255`

Description
===========

Method `\TYPO3\CMS\Core\Utility\File\ExtendedFileUtility::pushErrorMessagesToFlashMessageQueue()` has been marked as deprecated.


Impact
======

Using the method will trigger a deprecation log entry.


Affected Installations
======================

Instances with custom backend modules that use this method.


Migration
=========

Implement the method by yourself.

.. index:: PHP-API, FAL
